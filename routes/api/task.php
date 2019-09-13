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

        $date_ref = date("Y-m-d H:i:s");
        if(!empty($params["DATE_REF"])){
            $date_ref = date($params["INTERVAL_FROM"]);
        }

        $interval_from = date("Y-m-01 00:00:00");
        if(!empty($params["DATE_REF"])){
            $interval_from = date($params["INTERVAL_FROM"]);
            if(strlen($interval_to) == 10){
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


        if(empty(getenv("TASK_DIR"))) throw new Exception("ERROR - Tasks directory configuration empty");
        if(empty(getenv("TASK_SUFFIX"))) throw new Exception("ERROR - Wrong tasks configuration");

        $app_configs = $this->get('app_configs');
        $base_tasks_path = $app_configs["paths"]["base_path"] . getenv("TASK_DIR");

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
        foreach ($files as $taskFile) {

            $schedule = require $taskFile->getRealPath();
            if (!$schedule instanceof Schedule) {
                continue;
            }

            $aEVENTs = $schedule->events();

            foreach ($aEVENTs as $oEVENT) {
                $row = [];

                $row["filename"] = $taskFile->getFilename();
                $row["real_path"] = $taskFile->getRealPath();
                $row["subdir"] = str_replace( array( $row["filename"]),'',$taskFile->getPathname());
                $row["task_path"] = getenv("TASK_DIR") . str_replace($base_tasks_path, '', $row["real_path"]);
                $row["task_description"] = $oEVENT->description;

                $row["expression"] = $row["expression_orig"] = $oEVENT->getExpression();
                //$row["commnad"] = $oEVENT->getCommandForDisplay();

                $file_content = file_get_contents($taskFile->getRealPath(), true);
                $file_content = preg_replace('/(\/\/.*\\n)/', '', $file_content); //Remove commented lines
                $file_content = str_replace(array("\t","\n","\r"), '', $file_content);

                //$row["file_content"] = $file_content;

                $task_configuration = '';
                $start_pos = strpos($file_content, '$task->');
                $end_pos = strpos($file_content, ');', $start_pos);

                if ($start_pos === false || $end_pos === false){
                    throw new Exception("ERROR - Wrong tasks file format");
                }

                $task_configuration = substr($file_content, $start_pos+1, ($end_pos+1)-$start_pos);
                $row["task_configuration"] = $task_configuration;

                if(substr($row["expression"], 0, 3) == '* *' && substr($row["expression"], 4) != '* * *'){
                    $row["expression"] = '0 0'.substr($row["expression"],3);
                }

                $aCONFIGURATION = explode("->", $task_configuration = str_replace(array('task->',';'), '', $row["task_configuration"]));
                $row["task_configuration_exploded"] = json_encode($aCONFIGURATION);

                // $crunzUITaskGenerator = new \CrunzUI\Task\CrunzUITaskGenerator();

                // $row["task_configuration_explained"] = [];
                // $row["task_configuration_explained"]["configuration_type"] = '';
                // $row["task_configuration_explained"]["cron"] = '';
                // $row["task_configuration_explained"]["frequency"] = '';
                // $row["task_configuration_explained"]["individualFieldsSettings"] = array("days" => "", "hour" => "", "minute" => "", "dayOfMonth" => "", "month" => "", "dayOfWeek" => "",);

                // $row["task_configuration_explained"]["timeSet"] = array("configured" => false, "time" => "");
                // $row["task_configuration_explained"]["lifeTime"] = array("configured" => false, "from" => "", "to" => "");

                // $run_configuration_type = false;
                // foreach($aCONFIGURATION as $aCONFIGURATION_key => $configuration){

                //     if(!$run_configuration_type){
                //         if(strpos($configuration, "cron") !== false){
                //             $run_configuration_type = "CRON";
                //         }
                //     }

                //     if(!$run_configuration_type){
                //         foreach($crunzUITaskGenerator->getFrequencyDictionary() as $aFrequency_key => $frequency){
                //             if(substr($configuration, 0, strlen($frequency)) == $frequency){
                //                 $run_configuration_type = "LITERAL";
                //                 $row["task_configuration_explained"]["frequency"] = $configuration;
                //                 break;
                //             }
                //         }
                //     }

                //     if(!$run_configuration_type){
                //         foreach($crunzUITaskGenerator->getIindividualFieldsSettingsDictionary() as $aIindividualFieldsSettings_key => $individualFieldsSettings){
                //             if(substr($configuration, 0, strlen($individualFieldsSettings)) == $individualFieldsSettings){
                //                 $run_configuration_type = "INDIVIDUAL";
                //                 break;
                //             }
                //         }
                //     }

                //     foreach($crunzUITaskGenerator->getTimeSetDictionary() as $aTimeSet_key => $timeSet){
                //         if(substr($configuration, 0, strlen($timeSet)) == $timeSet){
                //             $row["task_configuration_explained"]["timeSet"]["configured"] = true;
                //             $row["task_configuration_explained"]["timeSet"]["time"] = str_replace(array($timeSet."('", "')"),'', $configuration);
                //             break;
                //         }
                //     }

                //     foreach($crunzUITaskGenerator->getLifeTimeDictionary() as $aLifeTime_key => $lifeTime){
                //         if(substr($configuration, 0, strlen($lifeTime)) == $lifeTime){
                //             $row["task_configuration_explained"]["lifeTime"]["configured"] = true;
                //             //$row["task_configuration_explained"]["timeSetConf"] = str_replace(array($timeSet."('", "')"),'', $configuration);
                //             break;
                //         }
                //     }
                // }

                // $row["task_configuration_explained"]["configuration_type"] = $run_configuration_type;

                unset($cron);
                $cron = Cron\CronExpression::factory($row["expression"]);

                $row["next_run"] = $cron->getNextRunDate($date_ref, 0, true)->format('Y-m-d H:i:s');

                //Calculeted but not necessarily executed
                $row["last_run"] = $cron->getPreviousRunDate($date_ref, 0, true)->format('Y-m-d H:i:s');

                //Calculating run list of the interval
                $row["interval_run_lst"] = [];
                $nincrement = 0;
                $calc_run = false;

                while($nincrement < 1000){ //Use the same hard limit of cron-expression library
                    $calc_run = $cron->getNextRunDate($interval_from, $nincrement, true)->format('Y-m-d H:i:s');
                    if($calc_run > $interval_to){
                        break;
                    }

                    $row["interval_run_lst"][] = $calc_run;
                    $nincrement++;
                }

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
            }
        };

        $data = $aTASKs;

        return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    });


    $app->post('/', function ($request, $response, $args) {

        $data = [];

        if(empty(getenv("TASK_DIR"))) throw new Exception("ERROR - Tasks directory configuration empty");
        if(empty(getenv("TASK_SUFFIX"))) throw new Exception("ERROR - Wrong tasks configuration");

        $app_configs = $this->get('app_configs');
        $base_tasks_path = $app_configs["paths"]["base_path"] . getenv("TASK_DIR");

        $params = array_change_key_case($request->getParams(), CASE_UPPER);

        if(empty($params["OP_TYPE"])) throw new Exception("ERROR - Wrong operation type (1)");

        //Check task file name
        if(empty($params["TASK_NAME"])){
            throw new Exception("ERROR - Empty task name");
        }else{
            if(preg_match('/[^a-z_.\/0-9]/i', $params["TASK_NAME"]) || strtoupper( substr($params["TASK_NAME"], -3) ) != "PHP" ){
                throw new Exception("ERROR - Wrong task name format (a-zA-Z0-9_./)");
            }
        }



        if($params["OP_TYPE"] == 'INS'){

        }else if($params["OP_TYPE"] == 'MOD'){

        }else{
            throw new Exception("ERROR - Wrong operation type (2)");
        }





        // if(empty(getenv("TASK_NAME"))) throw new Exception("ERROR - Wrong tasks configuration");
        // if(empty(getenv("SUBDIR"))) throw new Exception("ERROR - Wrong tasks configuration");
        // if(empty(getenv("TASK_DECRIPTION"))) throw new Exception("ERROR - Wrong tasks configuration");
        // if(empty(getenv("STATUS"))) throw new Exception("ERROR - Wrong tasks configuration");
        // if(empty(getenv("COMMAND"))) throw new Exception("ERROR - Wrong tasks configuration");



        // while (!\file_exists($path->toString())) {


        // $test = new \Crunz\Filesystem\Filesystem();
        // $pippo = $test->projectRootDirectory();

        // print_r($pippo);
        // die("----");



        // public function projectRootDirectory()
        // {
        //     if (null === $this->projectRootDir) {
        //         $dir = $rootDir = \dirname(__DIR__);
        //         $path = Path::fromStrings($dir, 'composer.json');

        //         while (!\file_exists($path->toString())) {
        //             if ($dir === \dirname($dir)) {
        //                 return $this->projectRootDir = $rootDir;
        //             }
        //             $dir = \dirname($dir);
        //             $path = Path::fromStrings($dir, 'composer.json');
        //         }

        //         $this->projectRootDir = $dir;
        //     }

        //     return $this->projectRootDir;
        // }



        // $test = new \CrunzUI\Task\CrunzUITaskGenerator();



        // print_r($test);
        // die();

        // $data = [];

        // $data[] = __DIR__;

        //$data = $aTASKs;

        return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    });

    $app->post('/upload', function ($request, $response, $args) {

        $data = [];

        $params = array_change_key_case($request->getParams(), CASE_UPPER);

        $data["PARAM"] = print_r($params, true);
        $data["FILE"] = print_r($_FILES, true);

        // $errors = []; // Store all foreseen and unforseen errors here

        // $fileExtensions = ['php','PHP']; // Get all the file extensions

        // $fileName = $_FILES['myfile']['name'];
        // $fileSize = $_FILES['myfile']['size'];
        // $fileTmpName  = $_FILES['myfile']['tmp_name'];
        // $fileType = $_FILES['myfile']['type'];
        // $fileExtension = strtolower(end(explode('.',$fileName)));

        // $uploadPath = $currentDir . $uploadDirectory . basename($fileName);

        // if (isset($_POST['submit'])) {

        //     if (! in_array($fileExtension,$fileExtensions)) {
        //         $errors[] = "This file extension is not allowed. Please upload a JPEG or PNG file";
        //     }

        //     if ($fileSize > 2000000) {
        //         $errors[] = "This file is more than 2MB. Sorry, it has to be less than or equal to 2MB";
        //     }

        //     // if (empty($errors)) {
        //     //     $didUpload = move_uploaded_file($fileTmpName, $uploadPath);

        //     //     if ($didUpload) {
        //     //         echo "The file " . basename($fileName) . " has been uploaded";
        //     //     } else {
        //     //         echo "An error occurred somewhere. Try again or contact the admin";
        //     //     }
        //     // } else {
        //     //     foreach ($errors as $error) {
        //     //         echo $error . "These are the errors" . "\n";
        //     //     }
        //     // }
        // }

        return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    });


    $app->delete('/', function ($request, $response, $args) {

        $data = [];

        $params = array_change_key_case($request->getParams(), CASE_UPPER);

        if(empty(getenv("TASK_DIR"))) throw new Exception("ERROR - Tasks directory configuration empty");
        if(empty(getenv("TASK_SUFFIX"))) throw new Exception("ERROR - Wrong tasks configuration");

        $app_configs = $this->get('app_configs');
        $base_tasks_path = $app_configs["paths"]["base_path"] . getenv("TASK_DIR");

        if(empty($params["TASK_PATH"])) throw new Exception("ERROR - No task file to delete submitted");

        if(
            strpos($params["TASK_PATH"], '.') !== false ||
            substr($params["TASK_PATH"], -strlen(getenv("TASK_SUFFIX"))) != getenv("TASK_SUFFIX") ||
            strpos($params["TASK_PATH"], getenv("TASK_DIR") === false)
        ){
            throw new Exception("ERROR - Task path out of range");
        }

        $data["path"] = $params["TASK_PATH"];

        try {
            if(!is_writable($base_tasks_path . $params["TASK_PATH"])) throw new Exception('ERROR - File not writable');

            unlink($base_tasks_path . $params["TASK_PATH"]);

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
