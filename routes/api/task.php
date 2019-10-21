<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;
use Tuupola\Base62;

use Crunz\Configuration\Configuration;
use Crunz\Schedule;
use Crunz\Filesystem;
use Crunz\Task\Collection;
use Crunz\Task\WrongTaskInstanceException;

foreach (glob(__DIR__ . '/../classes/*.php') as $filename){
    require_once $filename;
}

use CrunzUI\Task\CrunzUITaskGenerator;
use Lorisleiva\CronTranslator\CronTranslator;

$app->group('/task', function () use ($app) {

    $app->get('/group', function ($request, $response, $args) {

        $data = [];

        $params = array_change_key_case($request->getParams(), CASE_UPPER);

        $only_active = "Y";
        if(!empty($params["ONLY_ACTIVE"])){
            $only_active = $params["ONLY_ACTIVE"];
        }

        $app_configs = $this->get('app_configs');

        foreach($app_configs["task_groups"] as $row_cnt => $row_data){
            // if($only_active == 'Y' && !$row_data["disabled"]){
            //     continue;
            // }

            // unset($row_data["disabled"]);
            $data[] = $row_data;
        }

        return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    });

    $app->get('/', function ($request, $response, $args) {

        $data = [];

        $params = array_change_key_case($request->getParams(), CASE_UPPER);

        $calc_run_lst = "N";
        if(!empty($params["CALC_RUN_LST"])){
            $calc_run_lst = $params["CALC_RUN_LST"];
        }

        $date_ref = date("Y-m-d H:i:s");
        if(!empty($params["DATE_REF"])){
            $date_ref = date($params["INTERVAL_FROM"]);
        }

        $interval_from = date("Y-m-01 00:00:00");
        if(!empty($params["INTERVAL_FROM"])){
            $interval_from = date($params["INTERVAL_FROM"]);
            if(strlen($interval_from) == 10){
                $interval_from .= " 00:00:00";
            }
        }

        $interval_to = date("Y-m-t 23:59:59");
        if(!empty($params["INTERVAL_TO"])){
            $interval_to = date($params["INTERVAL_TO"]);
            if(strlen($interval_to) == 10){
                $interval_to .= " 23:59:59";
            }
        }


        if(empty(getenv("TASKS_DIR"))) throw new Exception("ERROR - Tasks directory configuration empty");
        if(empty(getenv("LOGS_DIR"))) throw new Exception("ERROR - Logs directory configuration empty");
        if(empty(getenv("TASK_SUFFIX"))) throw new Exception("ERROR - Wrong tasks configuration");

        $app_configs = $this->get('app_configs');
        $base_tasks_path = getenv("TASKS_DIR"); //Must be absolute path on server

        $directoryIterator = new \RecursiveDirectoryIterator($base_tasks_path);
        $recursiveIterator = new \RecursiveIteratorIterator($directoryIterator);


        $quotedSuffix = \preg_quote(getenv("TASK_SUFFIX"), '/');
        $regexIterator = new \RegexIterator( $recursiveIterator, "/^.+{$quotedSuffix}$/i", \RecursiveRegexIterator::GET_MATCH );

        $files = \array_map(
            static function (array $file) {
                return new \SplFileInfo(\reset($file));
            },
            \iterator_to_array($regexIterator)
        );


        $aTASKs = [];
        $task_counter = 0;
        foreach ($files as $taskFile) {

            require $taskFile->getRealPath();
            if (!$schedule instanceof Schedule) {
                continue;
            }

            $aEVENTs = $schedule->events();

            foreach ($aEVENTs as $oEVENT) {
                $row = [];
                $task_counter++;

                if(!empty($params["TASK_ID"])){
                    if($task_counter != $params["TASK_ID"]){
                        continue;
                    }
                }

                $row["filename"] = $taskFile->getFilename();
                $row["real_path"] = $taskFile->getRealPath();
                $row["subdir"] = str_replace( array( getenv("TASKS_DIR"), $row["filename"]),'',$row["real_path"]);
                $row["task_path"] = str_replace(getenv("TASKS_DIR"), '', $row["real_path"]);

                if(!empty($params["TASK_PATH"])){
                    if($row["task_path"] != $params["TASK_PATH"]){
                        continue;
                    }
                }

                $row["event_id"] = $oEVENT->getId();
                $row["event_launch_id"] = $task_counter;
                $row["task_description"] = $oEVENT->description;
                $row["expression"] = $row["expression_orig"] = $oEVENT->getExpression();

                $file_content = file_get_contents($taskFile->getRealPath(), true);
                $file_content = str_replace(array("   ","  ","\t","\n","\r"), ' ', $file_content);


                //Check task lifetime
                $from = '';
                $to = '';

                $delimiter = '#';
                $startTag = '->between(';
                $endTag = ')';
                $regex = $delimiter . preg_quote($startTag, $delimiter)
                                    . '(.*?)'
                                    . preg_quote($endTag, $delimiter)
                                    . $delimiter
                                    . 's';
                preg_match($regex, $file_content, $matches);
                if(!empty($matches) and strpos($matches[1], ',') !== false){
                    $aTIMELIFE = explode(",", $matches[1]);
                    $lifetime_from = strtotime( str_replace(array("'", "\""), '', $aTIMELIFE[0] ));
                    $lifetime_to = strtotime( str_replace(array("'", "\""), '', $aTIMELIFE[1] ));
                }

                if(empty($from)){
                    $delimiter = '#';
                    $startTag = '->from(';
                    $endTag = ')';
                    $regex = $delimiter . preg_quote($startTag, $delimiter)
                                        . '(.*?)'
                                        . preg_quote($endTag, $delimiter)
                                        . $delimiter
                                        . 's';
                    preg_match($regex, $file_content,$matches);
                    if(!empty($matches)){
                        $lifetime_from = strtotime( str_replace(array("'", "\""), '', $matches[1] ));
                    }
                }

                if(!empty($to)){
                    $delimiter = '#';
                    $startTag = '->to(';
                    $endTag = ')';
                    $regex = $delimiter . preg_quote($startTag, $delimiter)
                                        . '(.*?)'
                                        . preg_quote($endTag, $delimiter)
                                        . $delimiter
                                        . 's';
                    preg_match($regex, $file_content,$matches);
                    if(!empty($matches)){
                        $lifetime_to = strtotime( str_replace(array("'", "\""), '', $matches[1] ));
                    }
                }

                if(!empty($lifetime_from)){
                    $row["lifetime_from"] = date('Y-m-d H:i:s', $lifetime_from);
                    if($interval_from <  $row["lifetime_from"]){
                        $interval_from = $row["lifetime_from"];
                    }
                }
                if(!empty($lifetime_to)){
                    $row["lifetime_to"] = date('Y-m-d H:i:s', $lifetime_to);
                    if($interval_to >  $row["lifetime_to"]){
                        $interval_to = $row["lifetime_to"];
                    }
                }


                try {
                    $row["expression_readable"] = CronTranslator::translate($row["expression"]);
                } catch (Exception $e) {
                    $row["expression_readable"] = "";
                }

                //$row["commnad"] = $oEVENT->getCommandForDisplay();

                if(substr($row["expression"], 0, 3) == '* *' && substr($row["expression"], 4) != '* * *'){
                    $row["expression"] = '0 0'.substr($row["expression"],3);
                }

                unset($cron);
                $cron = Cron\CronExpression::factory($row["expression"]);

                //print_r(->format('Y-m-d H:i:s'));


                $row["next_run"] = $cron->getNextRunDate($date_ref, 0, true)->format('Y-m-d H:i:s');

                //Calculeted but not necessarily executed
                $row["last_run"] = $cron->getPreviousRunDate($date_ref, 0, true)->format('Y-m-d H:i:s');

                //Calculating run list of the interval
                $row["interval_run_lst"] = [];
                $nincrement = 0;
                $calc_run = false;

                if($calc_run_lst == "Y"){
                    while($nincrement < 1000){ //Use the same hard limit of cron-expression library
                        $calc_run = $cron->getNextRunDate($interval_from, $nincrement, true)->format('Y-m-d H:i:s');
                        if($calc_run > $interval_to){
                            break;
                        }

                        $row["interval_run_lst"][] = $calc_run;
                        $nincrement++;
                    }
                }



                //Log evaluations
                $log_name = rtrim( ltrim($row["task_path"],"/"), ".php" );
                $log_name = str_replace("/", "-", $log_name);


                $log_files = glob(getenv("LOGS_DIR")."/".$log_name."*.log"); //task-OK-20191001100-20191001110.log | task-KO-20191001100-20191001110.log
                usort( $log_files, function( $a, $b ) { return filemtime($b) - filemtime($a); } );

                $row["average_duration"] = 0;


                $row["last_outcome"] = '';
                $row["last_outcome_message"] = '';

                $task_status = $row["filename"]."_status";
                $row["status"] = 'active';
                if(isset($$task_status)){
                    if(!$$task_status){
                        $row["status"] = 'paused';
                    }
                }

                $aTASKs[] = $row;

                if(!empty($params["TASK_ID"])){
                    if($task_counter == $params["TASK_ID"]){
                        break;
                    }
                }

                if(!empty($params["TASK_PATH"])){
                    if($row["task_path"] == $params["TASK_PATH"]){
                        break;
                    }
                }
            }
        };

        $data = $aTASKs;

        return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    });

    $app->post('/execute', function ($request, $response, $args) {

        $data = [];

        $params = array_change_key_case($request->getParams(), CASE_UPPER);

        $exec_and_wait = "N";
        if(!empty($params["EXEC_AND_WAIT"])){
            $exec_and_wait = $params["EXEC_AND_WAIT"];
        }

        if(empty(getenv("TASKS_DIR"))) throw new Exception("ERROR - Tasks directory configuration empty");
        if(empty(getenv("TASK_SUFFIX"))) throw new Exception("ERROR - Wrong tasks configuration");

        $app_configs = $this->get('app_configs');
        $base_path =$app_configs["paths"]["base_path"];
        $base_tasks_path = getenv("TASKS_DIR"); //Must be absolute path on server

        if( empty($params["TASK_PATH"]) && empty($params["TASK_ID"]) ) throw new Exception("ERROR - No task path or task ID to execute submitted");

        if(empty(getenv("LOGS_DIR"))) throw new Exception("ERROR - Logs directory configuration empty");

        if(!is_dir(getenv("LOGS_DIR"))) throw new Exception('ERROR - Log destination path not exist');
        if(!is_writable(getenv("LOGS_DIR"))) throw new Exception('ERROR - Log directory not writable');


        $output = false;
        if( !empty($params["TASK_ID"]) ){
            $task_id = $params["TASK_ID"];
            $task_founded = true;
        }else{

            //File compliance check
            $path_check = str_replace(".php", '', $params["TASK_PATH"]);

            if(
                strpos($path_check, '.') !== false ||
                substr($params["TASK_PATH"], - strlen(getenv("TASK_SUFFIX"))) != getenv("TASK_SUFFIX") ||
                strpos($path_check, getenv("TASKS_DIR") === false)
            ){
                throw new Exception("ERROR - Task path out of range");
            }

            //List of all file with the same order used by Crunz
            $directoryIterator = new \RecursiveDirectoryIterator($base_tasks_path);
            $recursiveIterator = new \RecursiveIteratorIterator($directoryIterator);


            $quotedSuffix = \preg_quote(getenv("TASK_SUFFIX"), '/');
            $regexIterator = new \RegexIterator( $recursiveIterator, "/^.+{$quotedSuffix}$/i", \RecursiveRegexIterator::GET_MATCH );

            $files = \array_map(
                static function (array $file) {
                    return new \SplFileInfo(\reset($file));
                },
                \iterator_to_array($regexIterator)
            );

            $task_id = 1;
            $task_founded = false;
            foreach($files as $task_path => $task_data){
                //echo ($task_path." == ".$base_tasks_path."/".$params["TASK_PATH"])."\n";
                if($task_path == $base_tasks_path."/".ltrim($params["TASK_PATH"],"/")){
                    $task_founded = true;
                    break;
                }

                $task_id++;
            }
        }

        if($task_founded){

            $log_start = date("YmdHi");

            $log_name = rtrim( ltrim($params["TASK_PATH"],"/"), ".php" );
            $log_name = str_replace("/", "-", $log_name);
            $log_name_tmp = '.'.$log_name.'-'.$log_start.'log';
            $log_name = $log_name.'-'.$log_start;

            //die($log_name);

            // $log_files = glob(getenv("LOGS_DIR")."/".$log_name."*.log"); //task-OK-20191001100-20191001110.log | task-KO-20191001100-20191001110.log


            //if($exec_and_wait == 'Y'){
                $output = shell_exec("cd $base_tasks_path &&
                                      cd .. &&
                                      ./vendor/bin/crunz schedule:run -t$task_id -f -vvv > ". rtrim(getenv("LOGS_DIR")."/","/") . $log_name_tmp ."  &&
                                      END_DATA_LOG=date +%Y+%m+%d+%H+%M
                                      mv ". rtrim(getenv("LOGS_DIR")."/","/") . $log_name_tmp ." ". rtrim(getenv("LOGS_DIR")."/","/") . $log_name_tmp ."
                                      ");
            // }else{
            //     $output = shell_exec("cd $base_tasks_path && cd .. && ./vendor/bin/crunz schedule:run -t$task_id -f > /dev/null 2>&1 & ");
            // }

        }

        if($task_founded){
            $data["result"] = true;
            $data["exec_and_wait"] = $exec_and_wait;
            $data["result_msg"] = $output;
        }else{
            throw new Exception("ERROR - Execution path error");
        }

        return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    });


    // $app->post('/', function ($request, $response, $args) {

    //     $data = [];

    //     return $response->withStatus(200)
    //     ->withHeader("Content-Type", "application/json")
    //     ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    // });

    $app->post('/upload', function ($request, $response, $args) {

        $data = [];

        if(empty(getenv("TASKS_DIR"))) throw new Exception("ERROR - Tasks directory configuration empty");
        if(empty(getenv("TASK_SUFFIX"))) throw new Exception("ERROR - Wrong tasks configuration");

        $app_configs = $this->get('app_configs');
        $base_path =$app_configs["paths"]["base_path"];
        $base_tasks_path = getenv("TASKS_DIR"); //Must be absolute path on server


        $params = array_change_key_case($request->getParams(), CASE_UPPER);

        //Check destination
        if( empty($params["TASK_DESTINATION_PATH"]) ) throw new Exception("ERROR - No task path destination submitted");

        if(trim($params["TASK_DESTINATION_PATH"],"/") == ""){
            $destination_path = $base_tasks_path;
        }else{
            $destination_path = $base_tasks_path . "/".trim($params["TASK_DESTINATION_PATH"],"/");
        }

        if(!is_dir($destination_path)) throw new Exception('ERROR - Destination path not exist');
        if(!is_writable($destination_path)) throw new Exception('ERROR - File not writable');

        //Check task file
        if(
            empty($_FILES) ||
            empty($_FILES["TASK_UPLOAD"]) ||
            !is_uploaded_file($_FILES["TASK_UPLOAD"]["tmp_name"]) ||
            $_FILES["TASK_UPLOAD"]["error"] > 0
        ) throw new Exception("ERROR - No task file submitted or error uploading file");

        if( empty($_FILES["TASK_UPLOAD"]["name"]) ) throw new Exception("ERROR - No task file name submitted");

        if( substr($_FILES["TASK_UPLOAD"]["name"], - strlen(getenv("TASK_SUFFIX"))) != getenv("TASK_SUFFIX") ) throw new Exception("ERROR - Task file must end with '".getenv("TASK_SUFFIX")."'");


        $accepted_file_ext = ["php"];
        $aFILENAME = explode('.', $_FILES["TASK_UPLOAD"]["name"]);
        if(!in_array( strtolower( end ( $aFILENAME )),$accepted_file_ext)){
            throw new Exception("ERROR - Wrong task file extension");
        }

        if( empty($_FILES["TASK_UPLOAD"]["size"]) ) throw new Exception("ERROR - Zero byte task file submitted");

        //All check done.. Can upload
        if(!move_uploaded_file($_FILES["TASK_UPLOAD"]["tmp_name"], $destination_path."/".$_FILES["TASK_UPLOAD"]["name"])){
            throw new Exception("ERROR - Error uploading task file");
        }

        $data["result"] = true;
        $data["result_msg"] = '';

        return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    });


    $app->delete('/', function ($request, $response, $args) {

        $data = [];

        $params = array_change_key_case($request->getParams(), CASE_UPPER);

        if(empty(getenv("TASKS_DIR"))) throw new Exception("ERROR - Tasks directory configuration empty");
        if(empty(getenv("TASK_SUFFIX"))) throw new Exception("ERROR - Wrong tasks configuration");

        $app_configs = $this->get('app_configs');
        $base_tasks_path = getenv("TASKS_DIR"); //Must be absolute path on server

        if(empty($params["TASK_PATH"])) throw new Exception("ERROR - No task file to delete submitted");

        //File compliance check
        $path_check = str_replace(".php", '', $params["TASK_PATH"]);

        if(
            strpos($path_check, '.') !== false ||
            substr($params["TASK_PATH"], - strlen(getenv("TASK_SUFFIX"))) != getenv("TASK_SUFFIX") ||
            strpos($path_check, getenv("TASKS_DIR") === false)
        ){
            throw new Exception("ERROR - Task path out of range");
        }

        $data["path"] = $params["TASK_PATH"];
        $delete_path = $base_tasks_path."/".ltrim($params["TASK_PATH"],"/");

        try {
            if(!is_writable($delete_path)) throw new Exception('ERROR - File not writable');

            unlink($delete_path);

            $data["result"] = true;
            $data["result_msg"] = '';

        } catch(Exception $e) {
            $data["result"] = false;
            $data["result_msg"] = $e->getMessage();
        }

        return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    });
});
