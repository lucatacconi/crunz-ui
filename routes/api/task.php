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
use Symfony\Component\Yaml\Yaml;

$app->group('/task', function () use ($app) {

    $app->get('/group-v2', function ($request, $response, $args) {

        $data = [];

        $params = array_change_key_case($request->getParams(), CASE_UPPER);

        $only_active = "Y";
        if(!empty($params["ONLY_ACTIVE"])){
            $only_active = $params["ONLY_ACTIVE"];
        }

        $app_configs = $this->get('app_configs');
        $aGROUPs = $task_groups = $app_configs["task_groups"];



        // function recursiveRemoval(&$array){
        //     if(!empty($array["disabled"]) && $array["disabled"] == true){
        //         unset($array);
        //     }

        //     unset($array["disabled"]);

        //     if(!empty($array["children"]) && is_array($array["children"])){
        //         foreach($array["children"] as $key=>&$arrayElement)
        //         {
        //             if(!empty($arrayElement["disabled"]) && $arrayElement["disabled"]){
        //                 unset($array["children"][$key]);
        //             }else{
        //                 unset($array["children"][$key]["disabled"]);
        //                 recursiveRemoval($arrayElement);
        //             }
        //         }
        //     }
        // }

        // if($only_active == "Y"){
        //     recursiveRemoval($aGROUPs);
        // }

        $data = $aGROUPs;

        return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    });

    $app->get('/group-v1', function ($request, $response, $args) {

        $data = [];

        $params = array_change_key_case($request->getParams(), CASE_UPPER);

        $only_active = "Y";
        if(!empty($params["ONLY_ACTIVE"])){
            $only_active = $params["ONLY_ACTIVE"];
        }

        $app_configs = $this->get('app_configs');
        $main_group = $task_groups = $app_configs["task_groups"];

        unset($main_group["children"]);
        $data[] = $main_group;

        function setChildrenElementView(&$aLSTELEM, $parent, $only_active) {

            if(!empty($parent["children"])){
                foreach($parent["children"] as $row_cnt => $row_data){
                    if($only_active == 'Y' && $row_data["disabled"]){
                        continue;
                    }

                    unset($row_data["disabled"]);
                    $sub_parent = $row_data;

                    unset($sub_parent["children"]);
                    $aLSTELEM[] = $sub_parent;

                    setChildrenElementView($aLSTELEM, $row_data, $only_active);
                }
            }
        }

        setChildrenElementView($data, $task_groups, $only_active);

        return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    });

    $app->get('/group', function ($request, $response, $args) {

        $data = [];

        $params = array_change_key_case($request->getParams(), CASE_UPPER);

        $app_configs = $this->get('app_configs');

        foreach($app_configs["task_groups"] as $row_cnt => $row_data){
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

        $return_task_content = "N";
        if(!empty($params["RETURN_TASK_CONT"])){
            $return_task_content = $params["RETURN_TASK_CONT"];
        }

        $date_ref = date("Y-m-d H:i:s");
        if(!empty($params["DATE_REF"])){
            $date_ref = date($params["DATE_REF"]);
        }

        $date_now = date("Y-m-d H:i:s");

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


        $app_configs = $this->get('app_configs');
        $base_path =$app_configs["paths"]["base_path"];

        if(empty(getenv("CRUNZ_BASE_DIR"))){
            $crunz_base_dir = $base_path;
        }else{
            $crunz_base_dir = getenv("CRUNZ_BASE_DIR");
        }

        if(!file_exists ( $crunz_base_dir."/crunz.yml" )) throw new Exception("ERROR - Crunz.yml configuration file not found");
        $crunz_config_yml = file_get_contents($crunz_base_dir."/crunz.yml");

        if(empty($crunz_config_yml)) throw new Exception("ERROR - Crunz configuration file empty");

        try {
            $crunz_config = Yaml::parse($crunz_config_yml);
        } catch (ParseException $exception) {
            throw new Exception("ERROR - Crunz configuration file error");
        }

        if(empty($crunz_config["source"])) throw new Exception("ERROR - Tasks directory configuration empty");
        if(empty($crunz_config["suffix"])) throw new Exception("ERROR - Wrong tasks configuration");
        if(empty($crunz_config["timezone"])) throw new Exception("ERROR - Wrong timezone configuration");

        date_default_timezone_set($crunz_config["timezone"]);

        $TASKS_DIR = $crunz_base_dir . "/" . ltrim($crunz_config["source"], "/");
        $TASK_SUFFIX = $crunz_config["suffix"];

        if(empty(getenv("LOGS_DIR"))) throw new Exception("ERROR - Logs directory configuration empty");

        if(substr(getenv("LOGS_DIR"), 0, 2) == "./"){
            $LOGS_DIR = $base_path . "/" . getenv("LOGS_DIR");
        }else{
            $LOGS_DIR = getenv("LOGS_DIR");
        }

        if(!is_dir($LOGS_DIR)) throw new Exception('ERROR - Logs destination path not exist');
        if(!is_writable($LOGS_DIR)) throw new Exception('ERROR - Logs directory not writable');



        $base_tasks_path = $TASKS_DIR; //Must be absolute path on server

        $directoryIterator = new \RecursiveDirectoryIterator($base_tasks_path);
        $recursiveIterator = new \RecursiveIteratorIterator($directoryIterator);


        $quotedSuffix = \preg_quote($TASK_SUFFIX, '/');
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

            $file_content_orig = file_get_contents($taskFile->getRealPath(), true);
            $file_content = str_replace(array("   ","  ","\t","\n","\r"), ' ', $file_content_orig);

            if(
                strpos($file_content, 'use Crunz\Schedule;') === false ||
                strpos($file_content, '= new Schedule()') === false ||
                strpos($file_content, '->run(') === false ||
                strpos($file_content, 'return $schedule;') === false
            ){
                continue;
            }

            $file_check_result = exec("php -l \"".$taskFile->getRealPath()."\"");
            if(strpos($file_check_result, 'No syntax errors detected in') === false){
                //Syntax error in file
                continue;
            }

            require $taskFile->getRealPath();
            if (!$schedule instanceof Schedule) {
                continue;
            }

            $aEVENTs = $schedule->events();

            foreach ($aEVENTs as $oEVENT) {
                $row = [];
                $task_counter++;

                $event_interval_from = $interval_from;
                $event_interval_to = $interval_to;

                if(!empty($params["TASK_ID"])){
                    if($task_counter != $params["TASK_ID"]){
                        continue;
                    }
                }

                $row["filename"] = $taskFile->getFilename();
                $row["real_path"] = $taskFile->getRealPath();
                $row["subdir"] = str_replace( array( $TASKS_DIR, $row["filename"]),'',$row["real_path"]);
                $row["task_path"] = str_replace($TASKS_DIR, '', $row["real_path"]);

                if(!empty($params["TASK_PATH"])){
                    if($row["task_path"] != $params["TASK_PATH"]){
                        continue;
                    }
                }

                $row["event_id"] = $oEVENT->getId();
                $row["event_launch_id"] = $task_counter;
                $row["task_description"] = $oEVENT->description;
                $row["expression"] = $row["expression_orig"] = $oEVENT->getExpression();

                //Check task lifetime
                $from = '';
                $to = '';
                $lifetime_from = '';
                $lifetime_to = '';

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
                    if($event_interval_from <  $row["lifetime_from"]){
                        $event_interval_from = $row["lifetime_from"];
                    }
                }
                if(!empty($lifetime_to)){
                    $row["lifetime_to"] = date('Y-m-d H:i:s', $lifetime_to);
                    if($event_interval_to >  $row["lifetime_to"]){
                        $event_interval_to = $row["lifetime_to"];
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


                //Check log file configured by user appendOutputTo() or sendOutputTo()
                $custom_log = '';
                $delimiter = '#';
                $startTag = '->appendOutputTo(';
                $startTag2 = '->sendOutputTo(';
                $endTag = ')';
                $regex = $delimiter . preg_quote($startTag, $delimiter)
                                    . '(.*?)'
                                    . preg_quote($endTag, $delimiter)
                                    . $delimiter
                                    . 's';
                $regex2 = $delimiter . preg_quote($startTag2, $delimiter)
                                    . '(.*?)'
                                    . preg_quote($endTag, $delimiter)
                                    . $delimiter
                                    . 's';

                preg_match($regex, $file_content,$matches);
                if(!empty($matches) && empty($custom_log)){
                    $custom_log = str_replace(array("'", "\""), '', $matches[1] );
                }

                preg_match($regex2, $file_content,$matches);
                if(!empty($matches) && empty($custom_log)){
                    $custom_log = str_replace(array("'", "\""), '', $matches[1] );
                }

                if($return_task_content == "Y"){
                    $row["task_content"] = base64_encode($file_content_orig);
                }

                $row["custom_log"] = $custom_log;


                //Log evaluations
                $row["last_duration"] = 0;
                $row["last_outcome"] = '';
                $row["last_run"] = '';
                $row["executed_task_lst"] = [];


                $log_name = rtrim( ltrim($row["task_path"],"/"), ".php" );
                $log_name = str_replace("/", "", $log_name);
                $aLOGNAME = glob($LOGS_DIR."/".$log_name."*.log"); //task_OK_20191001100_20191001110_XXXX.log | task_KO_20191001100_20191001110_XXXX.log

                if(!empty($aLOGNAME)){
                    usort( $aLOGNAME, function( $a, $b ) { return filemtime($b) - filemtime($a); } );

                    //0 Path + name
                    //1 Outcome
                    //2 Start datetime
                    //3 End datetime
                    //4 Seed

                    $aLASTLOG =explode('_', str_replace($LOGS_DIR."/", "", $aLOGNAME[0]));

                    $row["last_outcome"] = $aLASTLOG[1];

                    $task_start = \DateTime::createFromFormat('YmdHi', $aLASTLOG[2]);
                    $task_stop = \DateTime::createFromFormat('YmdHi', $aLASTLOG[3]);
                    $interval = $task_start->diff($task_stop);

                    $row["last_duration"] = $interval->format('%i');
                    $row["last_run"] = $task_start->format('Y-m-d H:i:s');

                    if($calc_run_lst == "Y"){
                        foreach( $aLOGNAME as $aLOGNAME_key => $LOGFOCUS ){
                            $aLOGFOCUS =explode('_', str_replace($LOGS_DIR."/", "", $LOGFOCUS));
                            $task_start = DateTime::createFromFormat('YmdHi', $aLOGFOCUS[2]);
                            $task_stop = DateTime::createFromFormat('YmdHi', $aLOGFOCUS[3]);
                            $row["executed_task_lst"][$task_start->format('Y-m-d H:i:s')] = $task_stop->format('Y-m-d H:i:s');
                        }
                    }

                    $aFIRSTLOG = explode('_', str_replace($LOGS_DIR."/", "", end($aLOGNAME)));
                    $task_start = DateTime::createFromFormat('YmdHi', $aFIRSTLOG[2]);

                    if($event_interval_from < $task_start->format('Y-m-d H:i:s')){
                        $event_interval_from = $task_start->format('Y-m-d H:i:s');
                    }
                }else{
                    $event_interval_from = $date_now;
                }


                $row["next_run"] = $cron->getNextRunDate($date_ref, 0, true)->format('Y-m-d H:i:s');

                //Calculeted but not necessarily executed
                $row["calculeted_last_run"] = $cron->getPreviousRunDate($date_ref, 0, true)->format('Y-m-d H:i:s');

                //Calculating run list of the interval
                $row["interval_run_lst"] = [];
                $nincrement = 0;
                $calc_run = false;

                if($calc_run_lst == "Y"){
                    while($nincrement < 1000){ //Use the same hard limit of cron-expression library
                        $calc_run = $cron->getNextRunDate($event_interval_from, $nincrement, true)->format('Y-m-d H:i:s');
                        if($calc_run > $event_interval_to){
                            break;
                        }

                        $nincrement++;

                        if($calc_run < $date_now){
                            if(array_key_exists($calc_run, $row["executed_task_lst"])){
                                if($calc_run == $row["executed_task_lst"][$calc_run]){
                                    $row["interval_run_lst"][$calc_run] = date('Y-m-d H:i:s', strtotime("$calc_run + 1 minute"));
                                }else{
                                    $row["interval_run_lst"][$calc_run] = $row["executed_task_lst"][$calc_run];
                                }
                            }
                        }else{
                            $row["interval_run_lst"][$calc_run] = date('Y-m-d H:i:s', strtotime("$calc_run + ".($row["last_duration"] != 0 ? $row["last_duration"] : 1) ." minute"));
                        }
                    }

                    foreach($row["executed_task_lst"] as $exec_task_start => $exec_task_end){
                        if(!array_key_exists($exec_task_start, $row["interval_run_lst"])){
                            $row["interval_run_lst"][$exec_task_start] = $exec_task_end;
                        }
                    }

                    ksort($row["interval_run_lst"]);
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


    $app->get('/exec-outcome', function ($request, $response, $args) {

        $data = [];

        $params = array_change_key_case($request->getParams(), CASE_UPPER);


        $app_configs = $this->get('app_configs');
        $base_path =$app_configs["paths"]["base_path"];

        if(empty(getenv("CRUNZ_BASE_DIR"))){
            $crunz_base_dir = $base_path;
        }else{
            $crunz_base_dir = getenv("CRUNZ_BASE_DIR");
        }

        if(!file_exists ( $crunz_base_dir."/crunz.yml" )) throw new Exception("ERROR - Crunz.yml configuration file not found");
        $crunz_config_yml = file_get_contents($crunz_base_dir."/crunz.yml");

        if(empty($crunz_config_yml)) throw new Exception("ERROR - Crunz configuration file empty");

        try {
            $crunz_config = Yaml::parse($crunz_config_yml);
        } catch (ParseException $exception) {
            throw new Exception("ERROR - Crunz configuration file error");
        }

        if(empty($crunz_config["source"])) throw new Exception("ERROR - Tasks directory configuration empty");
        if(empty($crunz_config["suffix"])) throw new Exception("ERROR - Wrong tasks configuration");
        if(empty($crunz_config["timezone"])) throw new Exception("ERROR - Wrong timezone configuration");

        date_default_timezone_set($crunz_config["timezone"]);

        $TASKS_DIR = $crunz_base_dir . "/" . ltrim($crunz_config["source"], "/");
        $TASK_SUFFIX = $crunz_config["suffix"];

        if(empty(getenv("LOGS_DIR"))) throw new Exception("ERROR - Logs directory configuration empty");

        if(substr(getenv("LOGS_DIR"), 0, 2) == "./"){
            $LOGS_DIR = $base_path . "/" . getenv("LOGS_DIR");
        }else{
            $LOGS_DIR = getenv("LOGS_DIR");
        }

        if(!is_dir($LOGS_DIR)) throw new Exception('ERROR - Logs destination path not exist');
        if(!is_writable($LOGS_DIR)) throw new Exception('ERROR - Logs directory not writable');



        $base_tasks_path = $TASKS_DIR; //Must be absolute path on server

        if( empty($params["TASK_PATH"]) && empty($params["TASK_ID"]) ) throw new Exception("ERROR - No task path or task ID submitted");


        $directoryIterator = new \RecursiveDirectoryIterator($base_tasks_path);
        $recursiveIterator = new \RecursiveIteratorIterator($directoryIterator);


        $quotedSuffix = \preg_quote($TASK_SUFFIX, '/');
        $regexIterator = new \RegexIterator( $recursiveIterator, "/^.+{$quotedSuffix}$/i", \RecursiveRegexIterator::GET_MATCH );

        $files = \array_map(
            static function (array $file) {
                return new \SplFileInfo(\reset($file));
            },
            \iterator_to_array($regexIterator)
        );

        $aEXEC = [];
        $task_counter = 0;
        foreach ($files as $taskFile) {

            require $taskFile->getRealPath();
            if (!$schedule instanceof Schedule) {
                continue;
            }

            $aEVENTs = $schedule->events();

            foreach ($aEVENTs as $oEVENT) {
                $task_counter++;

                if(!empty($params["TASK_ID"])){
                    if($task_counter != $params["TASK_ID"]){
                        continue;
                    }
                }

                $task_filename = $taskFile->getFilename();
                $task_real_path = $taskFile->getRealPath();
                $task_subdir = str_replace( array( $TASKS_DIR, $task_filename),'',$task_real_path);
                $task_path = str_replace($TASKS_DIR, '', $task_real_path);

                if(!empty($params["TASK_PATH"])){
                    if($task_path != $params["TASK_PATH"]){
                        continue;
                    }
                }

                $aEXEC["task_path"] = $task_path;
                $aEXEC["task_id"] = $task_counter;


                //Get Crunz-ui log content
                $log_name = rtrim( ltrim($task_path,"/"), ".php" );
                $log_name = str_replace("/", "", $log_name);

                if(!empty($params["DATETIME_REF"])){

                    $datetime_ref = strtotime($params["DATETIME_REF"]);
                    if(!$datetime_ref) throw new Exception("ERROR - Date and time filter wrong format");

                    $datetime_ref = date('YmdHi', $datetime_ref);

                    $log_name_filter = $log_name."_*_".$datetime_ref."_*_*";
                    $aLOGNAME = glob($LOGS_DIR."/".$log_name_filter.".log");

                }else{
                    $aLOGNAME = glob($LOGS_DIR."/".$log_name."*.log");
                }

                if(!empty($aLOGNAME)){
                    usort( $aLOGNAME, function( $a, $b ) { return filemtime($b) - filemtime($a); } );

                    //0 Path + name
                    //1 Outcome
                    //2 Start datetime
                    //3 End datetime
                    //4 Seed

                    $aFOCUSLOG =explode('_', str_replace($LOGS_DIR."/", "", $aLOGNAME[0]));

                    $absolute_path = $aLOGNAME[0];
                    $outcome = $aFOCUSLOG[1];
                    $task_start = \DateTime::createFromFormat('YmdHi', $aFOCUSLOG[2]);
                    $task_stop = \DateTime::createFromFormat('YmdHi', $aFOCUSLOG[3]);
                    $interval = $task_start->diff($task_stop);
                    $duration = $interval->format('%i');

                }else{
                    throw new Exception("ERROR - No log file founded on server");
                }

                $aEXEC["outcome"] = $outcome;
                $aEXEC["task_start"] = $task_start->format('Y-m-d H:i');
                $aEXEC["task_stop"] = $task_stop->format('Y-m-d H:i');

                if($aEXEC["task_start"] == $aEXEC["task_stop"]){
                    $aEXEC["task_stop"] = date("Y-m-d H:i", strtotime($aEXEC["task_stop"]. '+1 minutes'));
                }

                $aEXEC["duration"] = $duration;

                $aEXEC["log_path"] = $absolute_path;

                $file_content = file_get_contents($aEXEC["log_path"], true);
                $aEXEC["log_content"] = base64_encode($file_content);


                //Check log file configured by user appendOutputTo() or sendOutputTo()
                $custom_log = '';
                $delimiter = '#';
                $startTag = '->appendOutputTo(';
                $startTag2 = '->sendOutputTo(';
                $endTag = ')';
                $regex = $delimiter . preg_quote($startTag, $delimiter)
                                    . '(.*?)'
                                    . preg_quote($endTag, $delimiter)
                                    . $delimiter
                                    . 's';
                $regex2 = $delimiter . preg_quote($startTag2, $delimiter)
                                    . '(.*?)'
                                    . preg_quote($endTag, $delimiter)
                                    . $delimiter
                                    . 's';

                preg_match($regex, $file_content,$matches);
                if(!empty($matches) && empty($custom_log)){
                    $custom_log_path = str_replace(array("'", "\""), '', $matches[1] );
                }

                preg_match($regex2, $file_content,$matches);
                if(!empty($matches) && empty($custom_log)){
                    $custom_log_path = str_replace(array("'", "\""), '', $matches[1] );
                }

                if(!empty($custom_log)){
                    $aEXEC["custom_log_path"] = $custom_log;

                    $file_content = file_get_contents($custom_log_path, true);
                    $aEXEC["custom_log_content"] = base64_encode($file_content);

                }else{
                    $aEXEC["custom_log_path"] = "";
                    $aEXEC["custom_log_content"] = "";
                }

                if(!empty($params["TASK_ID"])){
                    if($task_counter == $params["TASK_ID"]){
                        break;
                    }
                }

                if(!empty($params["TASK_PATH"])){
                    if($task_real_path == $params["TASK_PATH"]){
                        break;
                    }
                }
            }
        }

        $data = $aEXEC;

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

        $app_configs = $this->get('app_configs');
        $base_path =$app_configs["paths"]["base_path"];

        if(empty(getenv("CRUNZ_BASE_DIR"))){
            $crunz_base_dir = $base_path;
        }else{
            $crunz_base_dir = getenv("CRUNZ_BASE_DIR");
        }

        if(!file_exists ( $crunz_base_dir."/crunz.yml" )) throw new Exception("ERROR - Crunz.yml configuration file not found");
        $crunz_config_yml = file_get_contents($crunz_base_dir."/crunz.yml");

        if(empty($crunz_config_yml)) throw new Exception("ERROR - Crunz configuration file empty");

        try {
            $crunz_config = Yaml::parse($crunz_config_yml);
        } catch (ParseException $exception) {
            throw new Exception("ERROR - Crunz configuration file error");
        }

        if(empty($crunz_config["source"])) throw new Exception("ERROR - Tasks directory configuration empty");
        if(empty($crunz_config["suffix"])) throw new Exception("ERROR - Wrong tasks configuration");
        if(empty($crunz_config["timezone"])) throw new Exception("ERROR - Wrong timezone configuration");

        date_default_timezone_set($crunz_config["timezone"]);

        $TASKS_DIR = $crunz_base_dir . "/" . ltrim($crunz_config["source"], "/");
        $TASK_SUFFIX = $crunz_config["suffix"];

        if(empty(getenv("LOGS_DIR"))) throw new Exception("ERROR - Logs directory configuration empty");

        if(substr(getenv("LOGS_DIR"), 0, 2) == "./"){
            $LOGS_DIR = $base_path . "/" . getenv("LOGS_DIR");
        }else{
            $LOGS_DIR = getenv("LOGS_DIR");
        }

        if(!is_dir($LOGS_DIR)) throw new Exception('ERROR - Logs destination path not exist');
        if(!is_writable($LOGS_DIR)) throw new Exception('ERROR - Logs directory not writable');



        $base_tasks_path = $TASKS_DIR; //Must be absolute path on server

        if( empty($params["TASK_PATH"]) && empty($params["TASK_ID"]) ) throw new Exception("ERROR - No task path or task ID to execute submitted");


        //File compliance check
        if(!empty($params["TASK_PATH"])){

            $path_check = str_replace(".php", '', $params["TASK_PATH"]);

            if(
                strpos($path_check, '.') !== false ||
                substr($params["TASK_PATH"], - strlen($TASK_SUFFIX)) != $TASK_SUFFIX ||
                strpos($path_check, $TASKS_DIR === false)
            ){
                throw new Exception("ERROR - Task path out of range");
            }
        }


        //List of all file with the same order used by Crunz
        $directoryIterator = new \RecursiveDirectoryIterator($base_tasks_path);
        $recursiveIterator = new \RecursiveIteratorIterator($directoryIterator);


        $quotedSuffix = \preg_quote($TASK_SUFFIX, '/');
        $regexIterator = new \RegexIterator( $recursiveIterator, "/^.+{$quotedSuffix}$/i", \RecursiveRegexIterator::GET_MATCH );

        $files = \array_map(
            static function (array $file) {
                return new \SplFileInfo(\reset($file));
            },
            \iterator_to_array($regexIterator)
        );

        $task_id = 1;
        $task_founded = false;
        $task_path_founded = '';
        $aEXEC = [];
        $task_start = date("Y-m-d H:i");

        foreach($files as $task_path => $task_data){

            $task_path =  str_replace($TASKS_DIR, '', $task_path);

            if(!empty($params["TASK_ID"])){
                if($params["TASK_ID"] == $task_id){
                    $task_founded = true;
                    $task_path_founded = $task_path;
                    break;
                }
            }else{
                if($task_path == "/".ltrim($params["TASK_PATH"],"/")){
                    $task_founded = true;
                    $task_path_founded = $task_path;
                    break;
                }
            }

            $task_id++;
        }

        if($task_founded && !empty($task_path_founded)){

            $aEXEC["task_path"] = $task_path;
            $aEXEC["task_id"] = $task_id;
            $aEXEC["task_founded"] = $task_founded;
            $aEXEC["task_wait"] = $exec_and_wait;

            try {
                shell_exec("cd $base_tasks_path && cd .. && ./crunz-ui.sh -f -t $task_id -l $LOGS_DIR > /dev/null 2>&1 & ");

                $aEXEC["result"] = true;
                $aEXEC["result_msg"] = '';

            } catch(Exception $e) {

                $aEXEC["result"] = false;
                $aEXEC["result_msg"] = $e->getMessage();
            }

            if($exec_and_wait == 'Y'){

                $log_file_ready = false;
                $log_file_name = '';
                $round_cnt = 0;
                $max_round = 100;
                $datetime_init = date('YmdHis');
                $datetime_ref = date('YmdHi');

                $log_name_filter = str_replace("/", "", rtrim(ltrim($task_path_founded, "/"), ".php")) . "_*_" . $datetime_ref . "_*_*";

                while(!$log_file_ready && $round_cnt < $max_round){

                    $round_cnt++;

                    $aLOGNAME = glob($LOGS_DIR."/".$log_name_filter.".log");

                    if(!empty($aLOGNAME)){
                        usort( $aLOGNAME, function( $a, $b ) { return filemtime($b) - filemtime($a); } );

                        if(date ("YmdHis", filemtime($aLOGNAME[0])) >= $datetime_init){
                            $log_file_ready = true;
                            $log_file_name = $aLOGNAME[0];
                            break;
                        }
                    }

                    sleep(5);
                }

                if(empty($log_file_name)){
                    throw new Exception("ERROR - Task execution error");
                }


                $aEXEC["log_path"] = $log_file_name;

                $file_content = file_get_contents($aEXEC["log_path"], true);
                $aEXEC["log_content"] = base64_encode($file_content);

            }else{
                $aEXEC["result_msg"] = 'Task has been started at '.$task_start;
            }

            $data = $aEXEC;

        }else{
            throw new Exception("ERROR - Task to execute not found");
        }

        return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    });


    $app->post('/upload', function ($request, $response, $args) {

        $data = [];

        $params = array_change_key_case($request->getParams(), CASE_UPPER);


        $app_configs = $this->get('app_configs');
        $base_path =$app_configs["paths"]["base_path"];

        if(empty(getenv("CRUNZ_BASE_DIR"))){
            $crunz_base_dir = $base_path;
        }else{
            $crunz_base_dir = getenv("CRUNZ_BASE_DIR");
        }

        if(!file_exists ( $crunz_base_dir."/crunz.yml" )) throw new Exception("ERROR - Crunz.yml configuration file not found");
        $crunz_config_yml = file_get_contents($crunz_base_dir."/crunz.yml");

        if(empty($crunz_config_yml)) throw new Exception("ERROR - Crunz configuration file empty");

        try {
            $crunz_config = Yaml::parse($crunz_config_yml);
        } catch (ParseException $exception) {
            throw new Exception("ERROR - Crunz configuration file error");
        }

        if(empty($crunz_config["source"])) throw new Exception("ERROR - Tasks directory configuration empty");
        if(empty($crunz_config["suffix"])) throw new Exception("ERROR - Wrong tasks configuration");
        if(empty($crunz_config["timezone"])) throw new Exception("ERROR - Wrong timezone configuration");

        date_default_timezone_set($crunz_config["timezone"]);

        $TASKS_DIR = $crunz_base_dir . "/" . ltrim($crunz_config["source"], "/");
        $TASK_SUFFIX = $crunz_config["suffix"];

        if(!is_writable($TASKS_DIR)) throw new Exception('ERROR - Tasks directory not writable');

        if(empty(getenv("LOGS_DIR"))) throw new Exception("ERROR - Logs directory configuration empty");

        if(substr(getenv("LOGS_DIR"), 0, 2) == "./"){
            $LOGS_DIR = $base_path . "/" . getenv("LOGS_DIR");
        }else{
            $LOGS_DIR = getenv("LOGS_DIR");
        }

        if(!is_dir($LOGS_DIR)) throw new Exception('ERROR - Logs destination path not exist');
        if(!is_writable($LOGS_DIR)) throw new Exception('ERROR - Logs directory not writable');


        $base_tasks_path = $TASKS_DIR; //Must be absolute path on server


        $can_rewrite = "N";
        if(!empty($params["CAN_REWRITE"])){
            $can_rewrite = $params["CAN_REWRITE"];
        }


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
        if(!empty($_FILES)){
            $_FILES = array_change_key_case($_FILES, CASE_UPPER);
        }

        if(
            empty($_FILES) ||
            empty($_FILES["TASK_UPLOAD"]) ||
            !is_uploaded_file($_FILES["TASK_UPLOAD"]["tmp_name"]) ||
            $_FILES["TASK_UPLOAD"]["error"] > 0
        ) throw new Exception("ERROR - No task file submitted or error uploading file");

        if( empty($_FILES["TASK_UPLOAD"]["name"]) ) throw new Exception("ERROR - No task file name submitted");

        if( substr($_FILES["TASK_UPLOAD"]["name"], - strlen($TASK_SUFFIX)) != $TASK_SUFFIX ) throw new Exception("ERROR - Task file must end with '".$TASK_SUFFIX."'");


        $accepted_file_ext = ["php"];
        $aFILENAME = explode('.', $_FILES["TASK_UPLOAD"]["name"]);
        if(!in_array( strtolower( end ( $aFILENAME )),$accepted_file_ext)){
            throw new Exception("ERROR - Wrong task file extension");
        }

        if( empty($_FILES["TASK_UPLOAD"]["size"]) ) throw new Exception("ERROR - Zero byte task file submitted");

        if($can_rewrite != "Y"){
            if (file_exists($destination_path."/".$_FILES["TASK_UPLOAD"]["name"])) throw new Exception("ERROR - Same task file in the same position fouded. Can't overwrite");
        }


        //Check if file is a task file
        $file_content = file_get_contents($_FILES["TASK_UPLOAD"]["tmp_name"], true);
        $file_content = str_replace(array("   ","  ","\t","\n","\r"), ' ', $file_content);

        if(
            strpos($file_content, 'use Crunz\Schedule;') === false ||
            strpos($file_content, '= new Schedule()') === false ||
            strpos($file_content, '->run(') === false
        ){
            throw new Exception("ERROR - Wrong task configuration in task file");
        }


        //All check done.. Can upload task file
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

        $app_configs = $this->get('app_configs');
        $base_path =$app_configs["paths"]["base_path"];

        if(empty(getenv("CRUNZ_BASE_DIR"))){
            $crunz_base_dir = $base_path;
        }else{
            $crunz_base_dir = getenv("CRUNZ_BASE_DIR");
        }

        if(!file_exists ( $crunz_base_dir."/crunz.yml" )) throw new Exception("ERROR - Crunz.yml configuration file not found");
        $crunz_config_yml = file_get_contents($crunz_base_dir."/crunz.yml");

        if(empty($crunz_config_yml)) throw new Exception("ERROR - Crunz configuration file empty");

        try {
            $crunz_config = Yaml::parse($crunz_config_yml);
        } catch (ParseException $exception) {
            throw new Exception("ERROR - Crunz configuration file error");
        }

        if(empty($crunz_config["source"])) throw new Exception("ERROR - Tasks directory configuration empty");
        if(empty($crunz_config["suffix"])) throw new Exception("ERROR - Wrong tasks configuration");
        if(empty($crunz_config["timezone"])) throw new Exception("ERROR - Wrong timezone configuration");

        date_default_timezone_set($crunz_config["timezone"]);

        $TASKS_DIR = $crunz_base_dir . "/" . ltrim($crunz_config["source"], "/");
        $TASK_SUFFIX = $crunz_config["suffix"];

        if(!is_writable($TASKS_DIR)) throw new Exception('ERROR - Tasks directory not writable');

        if(empty(getenv("LOGS_DIR"))) throw new Exception("ERROR - Logs directory configuration empty");

        if(substr(getenv("LOGS_DIR"), 0, 2) == "./"){
            $LOGS_DIR = $base_path . "/" . getenv("LOGS_DIR");
        }else{
            $LOGS_DIR = getenv("LOGS_DIR");
        }

        if(!is_dir($LOGS_DIR)) throw new Exception('ERROR - Logs destination path not exist');
        if(!is_writable($LOGS_DIR)) throw new Exception('ERROR - Logs directory not writable');


        $base_tasks_path = $TASKS_DIR; //Must be absolute path on server

        if(empty($params["TASK_PATH"])) throw new Exception("ERROR - No task file to delete submitted");

        //File compliance check
        $path_check = str_replace(".php", '', $params["TASK_PATH"]);

        if(
            strpos($path_check, '.') !== false ||
            substr($params["TASK_PATH"], - strlen($TASK_SUFFIX)) != $TASK_SUFFIX ||
            strpos($path_check, $TASKS_DIR === false)
        ){
            throw new Exception("ERROR - Task path out of range");
        }

        $data["path"] = $params["TASK_PATH"];
        $delete_path = $base_tasks_path."/".ltrim($params["TASK_PATH"],"/");

        try {
            if(!file_exists($delete_path)) throw new Exception('ERROR - File not present');

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
