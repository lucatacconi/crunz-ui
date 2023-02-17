<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;

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


use Lorisleiva\CronTranslator\CronTranslator;
use Symfony\Component\Yaml\Yaml;

$app->group('/task-stat', function (RouteCollectorProxy $group) {

    $group->get('/period', function (Request $request, Response $response, array $args) {

        $data = [];

        $params = array_change_key_case($request->getQueryParams(), CASE_UPPER);

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

        $show_not_executed = "N";
        if(!empty($params["SHOW_NOT_EXECUTED"])){
            $show_not_executed = $params["SHOW_NOT_EXECUTED"];
        }

        $app_configs = $this->get('configs')["app_configs"];
        $base_path =$app_configs["paths"]["base_path"];

        if(empty($_ENV["CRUNZ_BASE_DIR"])){
            $crunz_base_dir = $base_path;
        }else{
            $crunz_base_dir = $_ENV["CRUNZ_BASE_DIR"];
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

        if(empty($_ENV["LOGS_DIR"])) throw new Exception("ERROR - Logs directory configuration empty");

        if(substr($_ENV["LOGS_DIR"], 0, 2) == "./"){
            $LOGS_DIR = $base_path . "/" . $_ENV["LOGS_DIR"];
        }else{
            $LOGS_DIR = $_ENV["LOGS_DIR"];
        }

        if(!is_dir($LOGS_DIR)) throw new Exception('ERROR - Logs destination path not exist');


        $aSTATs = [];
        $data_calc = substr($interval_from, 0, 10);

        while($data_calc <= substr($interval_to, 0, 10)){
            if(empty($aSTATs[$data_calc])){
                $aSTATs[$data_calc] = array( "planned" => 0, "planned_hft" => 0, "planned_std" => 0,
                                             "executed" => 0, "executed_hft" => 0, "executed_std" => 0,
                                             "error" => 0, "error_hft" => 0, "error_std" => 0,
                                             "succesfull" => 0, "executed_not_planned" => 0, "succesfull_not_planned" => 0, "error_not_planned" => 0, "syntax_error_task" => 0);

                if($show_not_executed == "Y"){
                    $aSTATs[$data_calc]["not_executed"] = [];
                }
            }

            $data_calc = date('Y-m-d', strtotime("$data_calc + 1 days"));
        }

        //Reading all log releted to the interval
        if( date('Y-m-d', strtotime($interval_from)) == date('Y-m-d', strtotime($interval_to)) ){

            $glob_filter = $LOGS_DIR."/";
            $glob_filter .= "*";
            $glob_filter .= date('Ymd', strtotime($interval_from))."*_";
            $glob_filter .= "*";
            $glob_filter .= ".log";

        }else{

            $glob_filter = $LOGS_DIR."/";
            $glob_filter .= "*";

            $glob_filter_from = '';
            $glob_filter_to = '';

            for($chr_selector = 0; $chr_selector < 10; $chr_selector++){

                if( substr(date('Ymd', strtotime($interval_from)), $chr_selector, 1) == substr(date('Ymd', strtotime($interval_to)), $chr_selector, 1) ){
                    $glob_filter_from .= substr(date('Ymd', strtotime($interval_from)), $chr_selector, 1);
                }else{
                    break;
                }
            }

            $glob_filter .= $glob_filter_from."*_";
            $glob_filter .= "*";
            $glob_filter .= ".log";
        }

        $aLOGNAME_all = glob($glob_filter); //UNIQUE_KEY_OK_20191001100_20191001110.log | UNIQUE_KEY_KO_20191001100_20191001110.log

        $aLOGNAME_perkey = [];
        foreach($aLOGNAME_all as $logkey => $logfile){
            $aLOG =explode('_', str_replace($LOGS_DIR."/", "", $logfile));

            if( empty($aLOGNAME_perkey[$aLOG[0]]) || count($aLOGNAME_perkey[$aLOG[0]]) == 0 ){
                $aLOGNAME_perkey[$aLOG[0]] = [];
            }

            $aLOGNAME_perkey[$aLOG[0]][] = $logfile;
        }

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


        $event_id = 0;

        foreach ($files as $taskFile) {

            $file_content_check = $file_content_orig = file_get_contents($taskFile->getRealPath(), true);
            $file_content = str_replace(array("   ","  ","\t","\n","\r"), ' ', $file_content_orig);

            $file_content_check = preg_replace('/\/\*[\s\S]+?\*\//', '', $file_content_check);
            $file_content_check = preg_replace('/\/\/[\s\S]+?\r/', '', $file_content_check);
            $file_content_check = preg_replace('/\/\/[\s\S]+?\n/', '', $file_content_check);

            if(
                strpos($file_content_check, 'use Crunz\Schedule;') === false ||
                strpos($file_content_check, '= new Schedule()') === false ||
                strpos($file_content_check, '->run(') === false ||
                strpos($file_content_check, 'return $schedule;') === false
            ){
                continue;
            }

            //Check presence of error
            $error_presence = false;
            if(filter_var($_ENV["CHECK_PHP_TASKS_SYNTAX"], FILTER_VALIDATE_BOOLEAN)){
                if(is_callable('shell_exec') && false === stripos(ini_get('disable_functions'), 'shell_exec')){
                    $file_check_result = exec("php -l \"".$taskFile->getRealPath()."\"");
                    if(strpos($file_check_result, 'No syntax errors detected in') === false){
                        //Syntax error in file
                        $error_presence = true;
                    }
                }
            }

            //Cron expression check
            $cron_presence = false;
            if(strpos($file_content_check, '->cron(\'') !== false){
                $pos_start = strpos($file_content_check, '->cron(\'');
                $cron_presence = true;
            }
            if(strpos($file_content_check, '->cron("') !== false){
                $pos_start = strpos($file_content_check, '->cron("');
                $cron_presence = true;
            }

            if($cron_presence){
                $cron_str_tmp = str_replace( ['->cron(\'', '->cron("'], '', substr($file_content_check, $pos_start) );
                $aTMP = explode(")", $cron_str_tmp);

                $cron_str = str_replace( ['\'', '"'], '', $aTMP[0] );

                try {
                    $cron_check = new Cron\CronExpression($cron_str);
                } catch (Exception $e) {
                    $error_presence = true;
                }
            }

            if($error_presence){
                $data_calc = substr($interval_from, 0, 10);
                while($data_calc <= substr($interval_to, 0, 10)){

                    $aSTATs[$data_calc]["syntax_error_task"]++;
                    $data_calc = date('Y-m-d', strtotime("$data_calc + 1 days"));
                }
                continue;
            }

            //If not jumped there are no errors in task
            unset($schedule);
            require $taskFile->getRealPath();
            if (empty($schedule) || !$schedule instanceof Schedule) {
                continue;
            }

            $aEVENTs = $schedule->events();

            $event_file_id = 0;
            foreach ($aEVENTs as $oEVENT) {

                $event_id++;
                $event_file_id++;

                $event_interval_from = $interval_from;
                $event_interval_to = $interval_to;

                $real_path = $taskFile->getRealPath();
                $task_path = str_replace($TASKS_DIR, '', $real_path);
                $task_description = $oEVENT->description;
                $expression = $oEVENT->getExpression();

                $event_unique_key = md5($task_path . $task_description . $expression);

                if(!empty($params["TASK_ID"])){
                    if($event_file_id != $params["TASK_ID"]){
                        continue;
                    }
                }

                if(!empty($params["TASK_PATH"])){
                    if($task_path != $params["TASK_PATH"]){
                        continue;
                    }
                }

                if(!empty($params["UNIQUE_ID"])){
                    if($event_unique_key != $params["UNIQUE_ID"]){
                        continue;
                    }
                }

                //Check task if it is high_frequency task (more then once an hour)
                $aEXPRESSION = explode(" ", $oEVENT->getExpression());
                $high_frequency = false;

                if( $aEXPRESSION[0] == '*' || strpos($aEXPRESSION[0], "-") !== false || strpos($aEXPRESSION[0], ",") !== false || strpos($aEXPRESSION[0], "/") !== false ){
                    $high_frequency = true;
                }

                if(!empty($params["TASK_TYPE"])){
                    if($params["TASK_TYPE"] == "STD"){
                        if($high_frequency){
                            continue;
                        }
                    }else if($params["TASK_TYPE"] == "HFT"){
                        if(!$high_frequency){
                            continue;
                        }
                    }
                }


                //Check task lifetime
                $life_datetime_from = '';
                $life_datetime_to = '';
                $life_time_from = '';
                $life_time_to = '';

                //Evaluate task lifetime between
                $matches_details = null;
                $pattern = '/->between\((.*?)\)/';
                preg_match_all($pattern, $file_content_check, $matches_between);

                foreach($matches_between[1] as $match_between){
                    $match_between = str_replace(' ', '', $match_between);

                    $pattern = '/["\'](\d{2}:\d{2} \d{4}-\d{2}-\d{2}|\d{4}-\d{2}-\d{2}|\d{2}:\d{2})["\'],["\'](\d{2}:\d{2} \d{4}-\d{2}-\d{2}|\d{4}-\d{2}-\d{2}|\d{2}:\d{2})["\']/';
                    preg_match_all($pattern, $match_between, $matches_details);

                    if(!empty($matches_details[0])){

                        if (preg_match('/^([0-9]*):([0-9]*)$/', $matches_details[1][0])) {
                            $life_time_from = $matches_details[1][0];
                        }else{
                            $life_datetime_from = $matches_details[1][0];
                        }

                        if (preg_match('/^([0-9]*):([0-9]*)$/', $matches_details[2][0])) {
                            $life_time_to = $matches_details[2][0];
                        }else{
                            $life_datetime_to = $matches_details[2][0];
                        }
                    }
                }

                //Evaluate task lifetime from
                $matches_details = null;
                $pattern = '/->from\(["\'](.*?)["\']\)/';
                preg_match_all($pattern, $file_content_check, $matches_from);

                if(!empty($matches_from[1])){
                    foreach($matches_from[1] as $match_from){
                        $pattern = '/(\d{2}:\d{2} \d{4}-\d{2}-\d{2}|\d{4}-\d{2}-\d{2}|\d{2}:\d{2})/';
                        preg_match_all($pattern, $match_from, $matches_details);

                        foreach($matches_details[1] as $match_tmp){
                            if (preg_match('/^([0-9]*):([0-9]*)$/', $match_tmp)) {
                                $life_time_from = $match_tmp;
                            }else{
                                $life_datetime_from = $match_tmp;
                            }
                        }
                    }
                }

                //Evaluate task lifetime to
                $matches_details = null;
                $pattern = '/->to\(["\'](.*?)["\']\)/';
                preg_match_all($pattern, $file_content_check, $matches_to);

                if(!empty($matches_to[1])){
                    foreach($matches_to[1] as $match_to){
                        $pattern = '/(\d{2}:\d{2} \d{4}-\d{2}-\d{2}|\d{4}-\d{2}-\d{2}|\d{2}:\d{2})/';
                        preg_match_all($pattern, $match_to, $matches_details);

                        foreach($matches_details[1] as $match_tmp){
                            if (preg_match('/^([0-9]*):([0-9]*)$/', $match_tmp)) {
                                $life_time_to = $match_tmp;
                            }else{
                                $life_datetime_to = $match_tmp;
                            }
                        }
                    }
                }

                if(!empty($life_datetime_from)){
                    $life_datetime_from = date('Y-m-d H:i:s', strtotime($life_datetime_from));
                }

                if(!empty($life_datetime_to)){
                    $life_datetime_to = date('Y-m-d H:i:s', strtotime($life_datetime_to));
                }

                if(!empty($life_time_from) and !empty($life_time_to)){
                    if(strtotime($life_time_from) - strtotime($life_time_to) >= 86400){
                        $life_time_from = '';
                        $life_time_to = '';
                    }
                }

                if(!empty($life_datetime_from)){
                    if($event_interval_to < $life_datetime_from){
                        continue;
                    }

                    if($event_interval_from < $life_datetime_from){
                        $event_interval_from = $life_datetime_from;
                    }
                }

                if(!empty($life_datetime_to)){
                    if($event_interval_from > $life_datetime_to){
                        continue;
                    }

                    if($life_datetime_to < $event_interval_to){
                        $event_interval_to = $life_datetime_to;
                    }
                }

                $aLOGNAME = [];
                $aLOGNAME_tmp = [];
                if(!empty($aLOGNAME_perkey[$event_unique_key])){
                    $aLOGNAME_tmp = $aLOGNAME_perkey[$event_unique_key];
                }

                if(!empty($aLOGNAME_tmp)){

                    //0 UNIQUE_KEY
                    //1 Outcome
                    //2 Start datetime
                    //3 End datetime

                    foreach( $aLOGNAME_tmp as $aLOGNAME_key => $LOGFOCUS ){
                        $aLOGFOCUS =explode('_', str_replace($LOGS_DIR."/", "", $LOGFOCUS));
                        $task_start = DateTime::createFromFormat('YmdHi', $aLOGFOCUS[2]);
                        $task_stop = DateTime::createFromFormat('YmdHi', $aLOGFOCUS[3]);

                        if($task_start->format('Y-m-d H:i:s') < $event_interval_from || $task_start->format('Y-m-d H:i:s') > $event_interval_to){
                            continue;
                        }

                        $aLOGNAME[$task_start->format('Y-m-d H:i')] = $aLOGFOCUS[1];
                    }
                }

                //Calculating run list of the interval
                unset($cron);
                $cron = new Cron\CronExpression($oEVENT->getExpression());

                $task_type = "std";
                if($high_frequency){
                    $task_type = "hft";
                }

                $date_ref = $event_interval_from; //date from selected by user or life_datetime_from if it is greater then event_interval_from

                $nincrement = 0;
                $step = 0;

                $date1 = new DateTime($event_interval_from);
                $date2 = new DateTime($event_interval_to);
                $diff = date_diff($date1, $date2);
                $round_limit = 1 + $diff->i + $diff->h * 60 + $diff->days * 24 * 60;

                while($nincrement < $round_limit){

                    $aDATEREF = explode(' ', $date_ref);

                    if(!empty($life_time_from) && date('Y-m-d H:i:s', strtotime($date_ref)) < date('Y-m-d H:i:s', strtotime($aDATEREF[0].' '.$life_time_from))){
                        $date_ref = date('Y-m-d H:i', strtotime($aDATEREF[0].' '.$life_time_from));

                        $step = 0;
                        try{
                            $date_ref = $cron->getNextRunDate($date_ref, $step, true)->format('Y-m-d H:i:s');
                            $nincrement++;
                        }catch(Exception $e){
                            break;
                        }
                        $step = 1;

                    }else{
                        try{
                            $date_ref = $cron->getNextRunDate($date_ref, $step, true)->format('Y-m-d H:i:s');
                            $nincrement++;
                        }catch(Exception $e){
                            break;
                        }
                    }

                    $aDATEREF = explode(' ', $date_ref);

                    if(!empty($life_time_to) && date('Y-m-d H:i:s', strtotime($date_ref)) > date('Y-m-d H:i:s', strtotime($aDATEREF[0].' '.$life_time_to))){
                        if(!empty($row["life_time_from"])){
                            $date_ref = date('Y-m-d H:i:s', strtotime($aDATEREF[0]." ".$life_time_from." +1 day"));
                        }else{
                            $date_ref = date('Y-m-d H:i:s', strtotime($aDATEREF[0]." +1 day"));
                        }

                        $step = 0;
                        continue;
                    }

                    if($date_ref < $event_interval_from){
                        continue;
                    }

                    if($date_ref > $event_interval_to){
                        break;
                    }

                    $step = 1;

                    $date_ref_short_10 = substr($date_ref, 0, 10);
                    $date_ref_short_16 = substr($date_ref, 0, 16);

                    $aSTATs[$date_ref_short_10]["planned"]++;
                    $aSTATs[$date_ref_short_10]["planned_$task_type"]++;

                    if(array_key_exists($date_ref_short_16, $aLOGNAME)){
                        $aSTATs[$date_ref_short_10]["executed"]++;
                        $aSTATs[$date_ref_short_10]["executed_$task_type"]++;

                        if($aLOGNAME[$date_ref_short_16] == 'OK'){
                            $aSTATs[$date_ref_short_10]["succesfull"]++;
                            $aSTATs[$date_ref_short_10]["succesfull_$task_type"]++;
                        }else{
                            $aSTATs[$date_ref_short_10]["error"]++;
                            $aSTATs[$date_ref_short_10]["error_$task_type"]++;
                        }

                        unset($aLOGNAME[$date_ref_short_16]);

                    }else{
                        if($show_not_executed == "Y"){
                            $aSTATs[$data_calc]["not_executed"][] = $date_ref." - ".$event_unique_key;
                        }
                    }
                }

                foreach($aLOGNAME as $log_datetime => $log_outcome){

                    $log_date = substr($log_datetime, 0, 10);

                    $aSTATs[$log_date]["executed_not_planned"]++;

                    if($log_outcome == 'OK'){
                        $aSTATs[$log_date]["succesfull_not_planned"]++;
                    }else{
                        $aSTATs[$log_date]["error_not_planned"]++;
                    }
                }

                if(!empty($params["TASK_ID"])){
                    if($event_file_id == $params["TASK_ID"]){
                        break;
                    }
                }

                if(!empty($params["TASK_PATH"])){
                    if($task_path == $params["TASK_PATH"]){
                        break;
                    }
                }

                if(!empty($params["UNIQUE_ID"])){
                    if($event_unique_key == $params["UNIQUE_ID"]){
                        break;
                    }
                }
            }
        }

        $data = $aSTATs;

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });

    $group->get('/uptime', function (Request $request, Response $response, array $args) {

        $data = [];


        $app_configs = $this->get('configs')["app_configs"];
        $base_path =$app_configs["paths"]["base_path"];

        if(empty($_ENV["CRUNZ_BASE_DIR"])){
            $crunz_base_dir = $base_path;
        }else{
            $crunz_base_dir = $_ENV["CRUNZ_BASE_DIR"];
        }

        $aFILES = array_diff(scandir($crunz_base_dir), array('..', '.'));

        $min_date = false;
        foreach($aFILES as $file_name){
            $file_path = $crunz_base_dir."/".$file_name;
            $file_date = date("y-m-d", filemtime($file_path));

            if($min_date === false || $file_date < $min_date){
                $min_date = $file_date;
            }
        }

        $data = [];
        $data["uptime-date"] = $min_date;
        $data["uptime-days"] = ceil((time() - strtotime($min_date)) / 86400);

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });

    $group->get('/logs', function (Request $request, Response $response, array $args) {

        $data = [];

        $app_configs = $this->get('configs')["app_configs"];
        $base_path =$app_configs["paths"]["base_path"];

        if(empty($_ENV["CRUNZ_BASE_DIR"])){
            $crunz_base_dir = $base_path;
        }else{
            $crunz_base_dir = $_ENV["CRUNZ_BASE_DIR"];
        }

        if(empty($_ENV["LOGS_DIR"])) throw new Exception("ERROR - Logs directory configuration empty");

        if(substr($_ENV["LOGS_DIR"], 0, 2) == "./"){
            $LOGS_DIR = $base_path . "/" . $_ENV["LOGS_DIR"];
        }else{
            $LOGS_DIR = $_ENV["LOGS_DIR"];
        }


        $log_size = 0;
        $num_files = 0;
        foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($LOGS_DIR)) as $file){
            if($file->isFile() && substr($file->getFilename(), -4) == ".log"){
                $log_size += $file->getSize();
                $num_files++;
            }
        }

        $log_size_mb = number_format(($log_size / 1024 / 1024), 2, '.', '');

        $data = [];
        $data["logs-size"] = $log_size_mb;
        $data["num-files"] = $num_files;

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });

    $group->get('/active-tasks', function (Request $request, Response $response, array $args) {

        $data = [];

        $app_configs = $this->get('configs')["app_configs"];
        $base_path =$app_configs["paths"]["base_path"];

        if(empty($_ENV["CRUNZ_BASE_DIR"])){
            $crunz_base_dir = $base_path;
        }else{
            $crunz_base_dir = $_ENV["CRUNZ_BASE_DIR"];
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

        $TASKS_DIR = $crunz_base_dir . "/" . ltrim($crunz_config["source"], "/");
        $TASK_SUFFIX = $crunz_config["suffix"];


        $directoryIterator = new \RecursiveDirectoryIterator($TASKS_DIR);
        $recursiveIterator = new \RecursiveIteratorIterator($directoryIterator);


        $quotedSuffix = \preg_quote($TASK_SUFFIX, '/');
        $regexIterator = new \RegexIterator( $recursiveIterator, "/^.+{$quotedSuffix}$/i", \RecursiveRegexIterator::GET_MATCH );

        $files = \array_map(
            static function (array $file) {
                return new \SplFileInfo(\reset($file));
            },
            \iterator_to_array($regexIterator)
        );

        $active_tasks_size = 0;
        $num_active_tasks = 0;
        foreach($files as $file){
            $active_tasks_size += intval($file->getSize());
            $num_active_tasks++;
        }

        $active_tasks_size = number_format(($active_tasks_size / 1024 ), 2, '.', '');

        $data = [];
        $data["files-size"] = $active_tasks_size;
        $data["num-files"] = $num_active_tasks;

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });

    $group->get('/archived-tasks', function (Request $request, Response $response, array $args) {

        $data = [];

        $app_configs = $this->get('configs')["app_configs"];
        $base_path =$app_configs["paths"]["base_path"];

        if(empty($_ENV["CRUNZ_BASE_DIR"])){
            $crunz_base_dir = $base_path;
        }else{
            $crunz_base_dir = $_ENV["CRUNZ_BASE_DIR"];
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

        $TASKS_DIR = $crunz_base_dir . "/" . ltrim($crunz_config["source"], "/");

        $archived_tasks_size = 0;
        $num_archived_tasks = 0;
        foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($TASKS_DIR)) as $file){
            if($file->isFile() && substr($file->getFilename(), -5) == ".arch"){
                $archived_tasks_size += $file->getSize();
                $num_archived_tasks++;
            }
        }

        $archived_tasks_size = number_format(($archived_tasks_size / 1024 ), 2, '.', '');

        $data = [];
        $data["files-size"] = $archived_tasks_size;
        $data["num-files"] = $num_archived_tasks;

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });

    $group->get('/executed-tasks', function (Request $request, Response $response, array $args) {

        $data = [];

        $params = array_change_key_case($request->getQueryParams(), CASE_UPPER);

        $date_ref = date("Y-m-d H:i:s");
        if(!empty($params["DATE_REF"])){
            $date_ref = date($params["DATE_REF"]);
        }

        $date_now = date("Y-m-d H:i:s");

        $interval_from = date("Y-m-d", strtotime('-1 month'));
        if(!empty($params["INTERVAL_FROM"])){
            $interval_from = date("Y-m-d", strtotime($params["INTERVAL_FROM"]));
        }

        $interval_to = date("Y-m-d", strtotime('-1 day'));
        if(!empty($params["INTERVAL_TO"])){
            $interval_to = date("Y-m-d", strtotime($params["INTERVAL_TO"]));
        }

        $app_configs = $this->get('configs')["app_configs"];
        $base_path =$app_configs["paths"]["base_path"];

        if(empty($_ENV["CRUNZ_BASE_DIR"])){
            $crunz_base_dir = $base_path;
        }else{
            $crunz_base_dir = $_ENV["CRUNZ_BASE_DIR"];
        }

        if(empty($_ENV["LOGS_DIR"])) throw new Exception("ERROR - Logs directory configuration empty");

        if(substr($_ENV["LOGS_DIR"], 0, 2) == "./"){
            $LOGS_DIR = $base_path . "/" . $_ENV["LOGS_DIR"];
        }else{
            $LOGS_DIR = $_ENV["LOGS_DIR"];
        }

        $num_executed = 0;
        $num_executed_with_errors = 0;

        $glob_filter = $LOGS_DIR."/";
        $glob_filter .= "*";
        $glob_filter .= ".log";

        $aLOGNAME_all = glob($glob_filter); //UNIQUE_KEY_OK_20191001100_20191001110.log | UNIQUE_KEY_KO_20191001100_20191001110.log

        $aLOGNAME_perkey = [];
        foreach($aLOGNAME_all as $logkey => $logfile){

            $aLOG =explode('_', str_replace($LOGS_DIR."/", "", $logfile));

            //0 UNIQUE_KEY
            //1 Outcome
            //2 Start datetime
            //3 End datetime

            $task_start = \DateTime::createFromFormat('YmdHi', $aLOG[2]);
            $task_stop = \DateTime::createFromFormat('YmdHi', $aLOG[3]);

            $task_start_Ymd = $task_start->format('Y-m-d');
            $task_stop_Ymd = $task_stop->format('Y-m-d');

            if ($task_start_Ymd >= $interval_from && $task_start_Ymd <= $interval_to){

                if($aLOG[1] == "OK"){
                    $num_executed++;
                }else{
                    $num_executed_with_errors++;
                }
            }
        }

        $data = [];
        $data["num-period"] = $num_executed;
        $data["num-period-with-errors"] = $num_executed_with_errors;

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });

    $group->get('/sys-load', function (Request $request, Response $response, array $args) {

        $data = [];

        $aLOAD = sys_getloadavg();

        $data["load-1"] = number_format($aLOAD[0], 2, '.', '');
        $data["load-5"] = number_format($aLOAD[1], 2, '.', '');
        $data["load-15"] = number_format($aLOAD[2], 2, '.', '');

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });
});
