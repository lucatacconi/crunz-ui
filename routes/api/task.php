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

$app->group('/task', function (RouteCollectorProxy $group) {

    $group->get('/', function (Request $request, Response $response, array $args) {


        //Parameters list
        // CALC_RUN_LST - Y | N - set API to show all planned task execution
        // PAST_PLANNED_TASKS - Y | N - Set API to show also planned task execution previous then today also if not executed. Set CALC_RUN_LST to Y
        // OUTCOME_EXECUTED_TASK_LST - Y | N - Shows the list of the results of the single executions
        // RETURN_TASK_CONT - Y | N - set API to show content of the task (PHP code)

        // DATE_REF - yyyy-mm-dd - Set reference date. Set to today if emtpy
        // INTERVAL_FROM - yyyy-mm-dd - Set the start date of the time range that will be evaluated. If not set, it will be set to the first of the current month
        // INTERVAL_TO - yyyy-mm-dd - Set the end date of the time range that will be evaluated. If not set, it will be set to the last day of the current month
        // TASK_ID - int - Select task by ID
        // TASK_PATH - path - Select task by path
        // UNIQUE_ID - id - Select task by Unique ID


        $data = [];

        $params = array_change_key_case($request->getQueryParams(), CASE_UPPER);

        $calc_run_lst = "N";
        if(!empty($params["CALC_RUN_LST"])){
            $calc_run_lst = $params["CALC_RUN_LST"];
        }

        $past_planned_tasks = "N";
        if(!empty($params["PAST_PLANNED_TASKS"])){
            $past_planned_tasks = $params["PAST_PLANNED_TASKS"];
        }
        if($past_planned_tasks == 'Y'){
            $calc_run_lst = 'Y';
        }

        $outcome_executed_task_lst = "N";
        if(!empty($params["OUTCOME_EXECUTED_TASK_LST"])){
            $outcome_executed_task_lst = $params["OUTCOME_EXECUTED_TASK_LST"];
        }
        if($outcome_executed_task_lst == 'Y'){
            $calc_run_lst = 'Y';
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

            unset($schedule);
            require $taskFile->getRealPath();
            if (empty($schedule) || !$schedule instanceof Schedule) {
                continue;
            }

            $aEVENTs = $schedule->events();

            $event_file_id = 0;
            foreach ($aEVENTs as $oEVENT) {
                $row = [];
                $task_counter++;
                $event_file_id++;

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
                $row["event_file_id"] = $event_file_id;
                $row["task_description"] = $oEVENT->description;
                $row["expression"] = $row["expression_orig"] = $oEVENT->getExpression();
                $row["event_unique_key"] = md5($row["task_path"] . $row["task_description"] . $row["expression"]);

                if(!empty($params["UNIQUE_ID"])){
                    if($row["event_unique_key"] != $params["UNIQUE_ID"]){
                        continue;
                    }
                }

                //Check task if it is high_frequency task (more then once an hour)
                $aEXPRESSION = explode(" ", $row["expression_orig"]);
                $row["high_frequency"] = false;
                $row["high_frequency_hour_round"] = 0;
                $row["high_frequency_day_round"] = 0;
                if( $aEXPRESSION[0] == '*' || strpos($aEXPRESSION[0], "-") !== false || strpos($aEXPRESSION[0], ",") !== false || strpos($aEXPRESSION[0], "/") !== false ){
                    $row["high_frequency"] = true;

                    $aFREQ_M = $aEXPRESSION[0];
                    $aFREQ_H = $aEXPRESSION[1];

                    $round_hour = 0;
                    $round_day = 0;

                    if($aFREQ_M == '*'){
                        $round_hour = 60;
                    }else if(strpos($aFREQ_M, "-") !== false){
                        $aINT = explode("-", $aFREQ_M);
                        $round_hour = ($aINT[1] - $aINT[0]);
                    }else if(strpos($aFREQ_M, "/") !== false){
                        $round_hour = round(60 / str_replace("*/", "", $aFREQ_M));
                    }else if(strpos($aFREQ_M, ",") !== false){
                        $aINT = explode("-", $aFREQ_M);
                        $round_hour = count($aINT);
                    }

                    $row["round-hour"] = true;
                    $row["high_frequency_hour_round"] = $round_hour;

                    if($aFREQ_H == '*'){
                        $round_day = $round_hour * 24;
                    }else if(strpos($aFREQ_H, "-") !== false){
                        $aINT = explode("-", $aFREQ_H);
                        $round_day = ($aINT[1] - $aINT[0]) * $round_hour;
                    }else if(strpos($aFREQ_H, "/") !== false){
                        $round_day = round(24 / str_replace("*/", "", $aFREQ_H)) * $round_hour;
                    }else if(strpos($aFREQ_H, ",") !== false){
                        $aINT = explode("-", $aFREQ_H);
                        $round_day = count($aINT) * $round_hour;
                    }

                    $row["high_frequency_day_round"] = $round_day;
                }


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

                if(empty($lifetime_from)){
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

                if(empty($lifetime_to)){
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

                $event_interval_from_orig = $event_interval_from;
                $event_interval_to_orig = $event_interval_to;


                if(!empty($row["lifetime_from"]) || !empty($row["lifetime_to"])){

                    $lifetime_descr = " (Executed";

                    if(!empty($row["lifetime_from"])){
                        $lifetime_descr .= " from ".$row["lifetime_from"];
                    }

                    if(!empty($row["lifetime_to"])){
                        $lifetime_descr .= " to ".$row["lifetime_to"];
                    }

                    $lifetime_descr .= ")";

                    $row["task_description"] = $row["task_description"].$lifetime_descr;
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

                $row["planned_in_interval"] = 0;
                $row["executed_in_interval"] = 0;
                $row["error_in_interval"] = 0;
                $row["succesfull_in_interval"] = 0;
                $row["last_outcome"] = '';


                $row["executed_task_lst"] = [];
                $row["outcome_executed_task_lst"] = [];

                //Looking for all the logs related to this event
                $aLOGNAME = glob($LOGS_DIR."/".$row["event_unique_key"]."*.log"); //T UNIQUE_KEY_OK_20191001100_20191001110.log | UNIQUE_KEY_KO_20191001100_20191001110.log

                if(!empty($aLOGNAME)){
                    usort( $aLOGNAME, function( $a, $b ) { return filemtime($b) - filemtime($a); } );

                    //0 UNIQUE_KEY
                    //1 Outcome
                    //2 Start datetime
                    //3 End datetime

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

                            if($task_start->format('Y-m-d H:i:s') < $event_interval_from || $task_start->format('Y-m-d H:i:s') > $event_interval_to){
                                continue;
                            }

                            $row["executed_in_interval"]++;
                            if($aLOGFOCUS[1] == "OK"){
                                $row["succesfull_in_interval"]++;
                            }else{
                                $row["error_in_interval"]++;
                            }

                            if($row["high_frequency"]){

                                if($aLOGNAME_key == 0){
                                    $row["executed_task_lst"][$task_start->format('Y-m-d H:i:s')] = $task_stop->format('Y-m-d H:i:s');
                                    if($outcome_executed_task_lst == "Y"){
                                        $row["outcome_executed_task_lst"][$task_start->format('Y-m-d H:i:s')] = $aLOGFOCUS[1];
                                    }
                                }else{

                                    $aLOGFOCUS_prev =explode('_', str_replace($LOGS_DIR."/", "", $aLOGNAME[$aLOGNAME_key - 1]));
                                    $task_start_prev = DateTime::createFromFormat('YmdHi', $aLOGFOCUS_prev[2]);
                                    $task_stop_prev = DateTime::createFromFormat('YmdHi', $aLOGFOCUS_prev[3]);

                                    if($task_start->format('Y-m-d') == $task_start_prev->format('Y-m-d')){
                                        continue;
                                    }else{
                                        $row["executed_task_lst"][$task_start->format('Y-m-d H:i:s')] = $task_stop->format('Y-m-d H:i:s');
                                        if($outcome_executed_task_lst == "Y"){
                                            $row["outcome_executed_task_lst"][$task_start->format('Y-m-d H:i:s')] = $aLOGFOCUS[1];
                                        }
                                    }
                                }

                            }else{
                                $row["executed_task_lst"][$task_start->format('Y-m-d H:i:s')] = $task_stop->format('Y-m-d H:i:s');
                                if($outcome_executed_task_lst == "Y"){
                                    $row["outcome_executed_task_lst"][$task_start->format('Y-m-d H:i:s')] = $aLOGFOCUS[1];
                                }
                            }
                        }
                    }

                    $aFIRSTLOG = explode('_', str_replace($LOGS_DIR."/", "", end($aLOGNAME)));
                    $task_start = DateTime::createFromFormat('YmdHi', $aFIRSTLOG[2]);

                }else{
                    if($past_planned_tasks != "Y"){
                        $event_interval_from = $date_now;
                    }
                }

                //Next run calculation
                if(empty($row["lifetime_from"]) && empty($row["lifetime_to"])){
                    $next_run = $cron->getNextRunDate($date_ref, 0, true)->format('Y-m-d H:i:s');
                }else if(!empty($row["lifetime_to"]) && $row["lifetime_to"] < $date_ref){
                    $next_run = '';
                }else{

                    $nincrement = 0;
                    $next_run = '';

                    while($nincrement < 1000){ //Use the same hard limit of cron-expression library
                        $calc_run = $cron->getNextRunDate($date_ref, $nincrement, true)->format('Y-m-d H:i:s');

                        if(!empty($row["lifetime_from"]) && empty($row["lifetime_to"])){
                            if($calc_run <= $row["lifetime_to"]  ){
                                $next_run = $calc_run;
                                break;
                            }
                        }else if(empty($row["lifetime_from"]) && !empty($row["lifetime_to"])){
                            if($calc_run <= $row["lifetime_to"]  ){
                                $next_run = $calc_run;
                                break;
                            }
                        }else if(!empty($row["lifetime_from"]) && !empty($row["lifetime_to"])){
                            if($calc_run >= $row["lifetime_from"] && $calc_run <= $row["lifetime_to"]  ){
                                $next_run = $calc_run;
                                break;
                            }
                        }

                        $nincrement++;
                    }
                }

                $row["next_run"] = $next_run;

                //Calculeted but not necessarily executed
                if(empty($row["lifetime_from"]) && empty($row["lifetime_to"])){
                    $calculeted_last_run = $cron->getPreviousRunDate($date_ref, 0, true)->format('Y-m-d H:i:s');
                }else if(!empty($row["lifetime_from"]) && $row["lifetime_from"] > $date_ref){
                    $calculeted_last_run = '';
                }else{

                    $nincrement = 0;
                    $calculeted_last_run = '';

                    while($nincrement < 1000){ //Use the same hard limit of cron-expression library
                        $calc_run = $cron->getPreviousRunDate($date_ref, $nincrement, true)->format('Y-m-d H:i:s');

                        if(!empty($row["lifetime_from"]) && empty($row["lifetime_to"])){
                            if($calc_run >= $row["lifetime_from"] ){
                                $calculeted_last_run = $calc_run;
                                break;
                            }
                        }else if(empty($row["lifetime_from"]) && !empty($row["lifetime_to"])){
                            if($calc_run <= $row["lifetime_to"]  ){
                                $calculeted_last_run = $calc_run;
                                break;
                            }
                        }else if(!empty($row["lifetime_from"]) && !empty($row["lifetime_to"])){
                            if($calc_run >= $row["lifetime_from"] && $calc_run <= $row["lifetime_to"]  ){
                                $calculeted_last_run = $calc_run;
                                break;
                            }
                        }

                        $nincrement++;
                    }
                }

                $row["calculeted_last_run"] = $calculeted_last_run;
                $row["executed_last_run"] = array_key_last($row["executed_task_lst"]);

                $row["last_run_actually_executed"] = false;
                $aLASTLOGs = glob($LOGS_DIR."/".$row["event_unique_key"].'_*_'. date("YmdHi", strtotime($row["calculeted_last_run"])) ."_*.log");
                if(!empty($aLASTLOGs)){
                    $row["last_run_actually_executed"] = true;
                }


                //Calculating run list of the interval
                $calc_run = false;
                $tmp_interval_lst = [];
                $nincrement = 0;

                if($row["high_frequency"]){

                    if($calc_run_lst == "Y"){
                        $row["interval_run_lst"] = [];
                    }

                    $calc_run_prec = '';
                    while(empty($calc_run_ref) || $calc_run_ref < $event_interval_to){

                        if(empty($calc_run_ref)){
                            $calc_run_ref = $event_interval_from_orig;
                        }

                        $calc_run_ref = $cron->getNextRunDate($calc_run_ref, $nincrement, true)->format('Y-m-d H:i:s');
                        if($nincrement == 0) $nincrement++;

                        $row["planned_in_interval"]++;

                        if($calc_run_ref < $date_now && $past_planned_tasks != "Y"){

                            if(array_key_exists($calc_run_ref, $row["executed_task_lst"])){
                                if($calc_run_ref == $row["executed_task_lst"][$calc_run_ref]){
                                    $row["interval_run_lst"][$calc_run_ref] = date('Y-m-d H:i:s', strtotime("$calc_run_ref + 1 minute"));
                                }else{
                                    $row["interval_run_lst"][$calc_run_ref] = $row["executed_task_lst"][$calc_run_ref];
                                }

                                $calc_run_prec = date('Y-m-d', strtotime($calc_run_ref));
                            }

                        }else{
                            if($calc_run_prec < date('Y-m-d', strtotime($calc_run_ref))){
                                $row["interval_run_lst"][$calc_run_ref] = date('Y-m-d H:i:s', strtotime("$calc_run_ref + ".($row["last_duration"] != 0 ? $row["last_duration"] : 1) ." minute"));
                                $calc_run_prec = date('Y-m-d', strtotime($calc_run_ref));
                            }else{
                                continue;
                            }
                        }
                    }

                }else{

                    if($calc_run_lst == "Y"){
                        $row["interval_run_lst"] = [];
                    }

                    while($nincrement < 1000){ //Use the same hard limit of cron-expression library
                        $calc_run = $cron->getNextRunDate($event_interval_from_orig, $nincrement, true)->format('Y-m-d H:i:s');

                        if($calc_run > $event_interval_to){
                            break;
                        }

                        $nincrement++;

                        $tmp_interval_lst[$calc_run] = $calc_run;

                        if($calc_run < $event_interval_from){
                            continue;
                        }

                        if($calc_run_lst == "Y"){
                            if($calc_run < $date_now && $past_planned_tasks != "Y"){
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
                    }

                    foreach($row["executed_task_lst"] as $exec_task_start => $exec_task_end){
                        if($exec_task_start >= $event_interval_from_orig && $exec_task_start <= $event_interval_to && !array_key_exists($exec_task_start, $tmp_interval_lst)){
                            $tmp_interval_lst[$calc_run] = $calc_run;
                        }
                    }

                    if($calc_run_lst == "Y"){
                        foreach($row["executed_task_lst"] as $exec_task_start => $exec_task_end){
                            if($exec_task_start >= $event_interval_from && $exec_task_start <= $event_interval_to && !array_key_exists($exec_task_start, $row["interval_run_lst"])){
                                $row["interval_run_lst"][$exec_task_start] = $exec_task_end;
                            }
                        }

                        ksort($row["interval_run_lst"]);
                    }

                    $row["planned_in_interval"] = count($tmp_interval_lst);
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

                if(!empty($params["UNIQUE_ID"])){
                    if($row["event_unique_key"] == $params["UNIQUE_ID"]){
                        break;
                    }
                }
            }
        };

        $data = $aTASKs;

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });

    $group->get('/exec-outcome', function (Request $request, Response $response, array $args) {

        $data = [];

        $params = array_change_key_case($request->getQueryParams(), CASE_UPPER);

        if( empty($params["EVENT_UNIQUE_KEY"]) && empty($params["TASK_ID"]) ) throw new Exception("ERROR - No event unique key or task ID submitted");

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

        $aEXEC = [];
        $task_counter = 0;
        foreach ($files as $taskFile) {

            unset($schedule);
            require $taskFile->getRealPath();
            if (empty($schedule) || !$schedule instanceof Schedule) {
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

                $event_unique_key = md5($task_path . $oEVENT->description . $oEVENT->getExpression());

                if(!empty($params["EVENT_UNIQUE_KEY"])){
                    if($event_unique_key != $params["EVENT_UNIQUE_KEY"]){
                        continue;
                    }
                }

                $aEXEC["task_path"] = $task_path;
                $aEXEC["task_id"] = $task_counter;
                $aEXEC["event_unique_key"] = $event_unique_key;


                //Get Crunz-ui log content
                // UNIQUE_KEY_OK_20191001100_20191001110.log
                $log_name = $event_unique_key;

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

                if(!empty($params["EVENT_UNIQUE_KEY"])){
                    if($event_unique_key == $params["EVENT_UNIQUE_KEY"]){
                        break;
                    }
                }
            }
        }

        $data = $aEXEC;

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });

    $group->post('/', function (Request $request, Response $response, array $args) {

        $data = [];

        $params = array_change_key_case($request->getQueryParams(), CASE_UPPER);


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

        if(!is_writable($TASKS_DIR)) throw new Exception('ERROR - Tasks directory not writable');

        if(empty($_ENV["LOGS_DIR"])) throw new Exception("ERROR - Logs directory configuration empty");

        if(substr($_ENV["LOGS_DIR"], 0, 2) == "./"){
            $LOGS_DIR = $base_path . "/" . $_ENV["LOGS_DIR"];
        }else{
            $LOGS_DIR = $_ENV["LOGS_DIR"];
        }

        if(!is_dir($LOGS_DIR)) throw new Exception('ERROR - Logs destination path not exist');
        if(!is_writable($LOGS_DIR)) throw new Exception('ERROR - Logs directory not writable');


        $base_tasks_path = $TASKS_DIR; //Must be absolute path on server


        //Check destination
        if( empty($params["TASK_FILE_PATH"]) ) throw new Exception("ERROR - No task file path submitted");

        if (preg_match('/[^a-zA-Z0-9\\/\._-]/', trim($params["TASK_FILE_PATH"],"/") )) {
            throw new Exception("ERROR - Task file name contains not allowed characters (Only a-z, A-Z, 0-9, -, _, ., / characters allowed)");
        }

        if(trim($params["TASK_FILE_PATH"],"/") == ""){
            $task_file_path = $base_tasks_path;
        }else{
            $task_file_path = $base_tasks_path . "/".trim($params["TASK_FILE_PATH"],"/");
        }

        $task_file_path = str_replace(['//'], '/', $task_file_path);


        if( !empty($params["NEW_FILE"]) && $params["NEW_FILE"] == 'Y'){

            if(strpos($task_file_path, $TASK_SUFFIX) === false){
                throw new Exception("ERROR - Task file name doesn't contains Crunz suffix and '.php' file extension");
            }

            if(file_exists($task_file_path)) throw new Exception('ERROR - Task file already exist');
        }else{
            if(!file_exists($task_file_path)) throw new Exception('ERROR - Task file not exist');
            if(!is_writable($task_file_path)) throw new Exception('ERROR - File not writable');
        }

        $task_handle = fopen($task_file_path, "wb");
        if($task_handle === false) throw new Exception('ERROR - File open error');

        if( empty($params["TASK_CONTENT"]) ) throw new Exception("ERROR - No task content submitted");


        $params["TASK_CONTENT"] = base64_decode($params["TASK_CONTENT"]);
        $file_content = str_replace(array("   ","  ","\t","\n","\r"), ' ', $params["TASK_CONTENT"]);

        if(
            strpos($file_content, 'use Crunz\Schedule;') === false ||
            strpos($file_content, '= new Schedule()') === false ||
            strpos($file_content, '->run(') === false
        ){
            throw new Exception("ERROR - Wrong task configuration in task file");
        }

        try {
            fwrite($task_handle, $params["TASK_CONTENT"]);
            fclose($task_handle);

            $data["result"] = true;
            $data["result_msg"] = '';

        } catch(Exception $e) {
            $data["result"] = false;
            $data["result_msg"] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });

    $group->post('/execute', function (Request $request, Response $response, array $args) {

        $data = [];

        $params = array_change_key_case($request->getQueryParams(), CASE_UPPER);

        if( empty($params["EVENT_UNIQUE_KEY"]) && empty($params["TASK_ID"]) ) throw new Exception("ERROR - No event unique key or task ID to execute submitted");

        $exec_and_wait = "N";
        if(!empty($params["EXEC_AND_WAIT"])){
            $exec_and_wait = $params["EXEC_AND_WAIT"];
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
        if(!is_writable($LOGS_DIR)) throw new Exception('ERROR - Logs directory not writable');

        $base_tasks_path = $TASKS_DIR; //Must be absolute path on server


        if( !file_exists( $crunz_base_dir."/crunz-ui.sh" ) || !is_executable ( $crunz_base_dir."/crunz-ui.sh" )){
            throw new Exception("ERROR - Crunz-ui.sh is not present in Crunz base path or is not executable. Copy the file to the correct destination.");
        }

        if( !file_exists( $crunz_base_dir."/TasksTreeReader.php" )){
            throw new Exception("ERROR - TasksTreeReader.php is not present in Crunz base path. Copy the file to the correct destination.");
        }


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


        $task_id = 0;
        $task_founded = false;
        $task_path_founded = '';
        $aEXEC = [];
        $task_start = date("Y-m-d H:i");

        foreach ($files as $taskFile) {

            unset($schedule);
            require $taskFile->getRealPath();
            if (empty($schedule) || !$schedule instanceof Schedule) {
                continue;
            }

            $aEVENTs = $schedule->events();

            foreach ($aEVENTs as $oEVENT) {
                $task_id++;

                if(!empty($params["TASK_ID"])){
                    if($task_id != $params["TASK_ID"]){
                        continue;
                    }
                }

                $task_real_path = $taskFile->getRealPath();
                $task_path = str_replace($TASKS_DIR, '', $task_real_path);
                $event_unique_key = md5($task_path . $oEVENT->description . $oEVENT->getExpression());

                if(!empty($params["EVENT_UNIQUE_KEY"])){
                    if($event_unique_key != $params["EVENT_UNIQUE_KEY"]){
                        continue;
                    }
                }

                $task_filename = $taskFile->getFilename();
                $task_real_path = $taskFile->getRealPath();
                $task_subdir = str_replace( array( $TASKS_DIR, $task_filename),'',$task_real_path);


                $aEXEC["event_unique_key"] = $event_unique_key;
                $aEXEC["task_path"] = $task_path;
                $aEXEC["task_id"] = $task_id;

                if(!empty($params["TASK_ID"])){
                    if($task_id == $params["TASK_ID"]){
                        $task_founded = true;
                        $task_path_founded = $task_path;
                        break;
                    }
                }

                if(!empty($params["EVENT_UNIQUE_KEY"])){
                    if($event_unique_key == $params["EVENT_UNIQUE_KEY"]){
                        $task_founded = true;
                        $task_path_founded = $task_path;
                        break;
                    }
                }
            }
        }

        if($task_founded && !empty($task_path_founded)){

            $aEXEC["task_founded"] = $task_founded;
            $aEXEC["task_wait"] = $exec_and_wait;

            try {
                shell_exec("cd $base_tasks_path && cd .. && ./crunz-ui.sh -f -t ".$aEXEC["task_id"]." -l $LOGS_DIR > /dev/null 2>&1 & ");

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

                $log_name = $aEXEC["event_unique_key"];
                $log_name_filter = $log_name."_*_".$datetime_ref."_*_*";

                // throw new Exception($log_name_filter);

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

                    sleep(1);
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

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });

    $group->post('/upload', function (Request $request, Response $response, array $args) {

        $data = [];

        $params = array_change_key_case($request->getQueryParams(), CASE_UPPER);
        $paramsBody = array_change_key_case($request->getParsedBody(), CASE_UPPER);

        $params = array_merge($params, $paramsBody);

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

        if(!is_writable($TASKS_DIR)) throw new Exception('ERROR - Tasks directory not writable');

        if(empty($_ENV["LOGS_DIR"])) throw new Exception("ERROR - Logs directory configuration empty");

        if(substr($_ENV["LOGS_DIR"], 0, 2) == "./"){
            $LOGS_DIR = $base_path . "/" . $_ENV["LOGS_DIR"];
        }else{
            $LOGS_DIR = $_ENV["LOGS_DIR"];
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

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });

    $group->delete('/', function (Request $request, Response $response, array $args) {

        $data = [];

        $params = array_change_key_case($request->getQueryParams(), CASE_UPPER);

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

        if(!is_writable($TASKS_DIR)) throw new Exception('ERROR - Tasks directory not writable');

        if(empty($_ENV["LOGS_DIR"])) throw new Exception("ERROR - Logs directory configuration empty");

        if(substr($_ENV["LOGS_DIR"], 0, 2) == "./"){
            $LOGS_DIR = $base_path . "/" . $_ENV["LOGS_DIR"];
        }else{
            $LOGS_DIR = $_ENV["LOGS_DIR"];
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

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });


    $group->post('/archive', function (Request $request, Response $response, array $args) {

        $data = [];

        $params = array_change_key_case($request->getQueryParams(), CASE_UPPER);

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

        if(!is_writable($TASKS_DIR)) throw new Exception('ERROR - Tasks directory not writable');

        if(empty($_ENV["LOGS_DIR"])) throw new Exception("ERROR - Logs directory configuration empty");

        if(substr($_ENV["LOGS_DIR"], 0, 2) == "./"){
            $LOGS_DIR = $base_path . "/" . $_ENV["LOGS_DIR"];
        }else{
            $LOGS_DIR = $_ENV["LOGS_DIR"];
        }

        if(!is_dir($LOGS_DIR)) throw new Exception('ERROR - Logs destination path not exist');
        if(!is_writable($LOGS_DIR)) throw new Exception('ERROR - Logs directory not writable');


        $base_tasks_path = $TASKS_DIR; //Must be absolute path on server

        if(empty($params["TASK_PATH"])) throw new Exception("ERROR - No task file to archive submitted");

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
        $file_to_archive = $base_tasks_path."/".ltrim($params["TASK_PATH"],"/");

        try {

            if(!file_exists($file_to_archive)) throw new Exception('ERROR - File not present');

            if(!is_writable($file_to_archive)) throw new Exception('ERROR - File not writable');

            $out = rename($file_to_archive, str_replace(".php", ".arch", $file_to_archive));
            if(!$out){
                throw new Exception('ERROR - Archive error');
            }

            $data["result"] = true;
            $data["result_msg"] = '';

        } catch(Exception $e) {
            $data["result"] = false;
            $data["result_msg"] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });
});
