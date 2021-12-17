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

$app->group('/task-archive', function (RouteCollectorProxy $group) {

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

        // throw new Exception(print_r($params, true));

        $date_ref = date("Y-m-d H:i:s");
        if(!empty($params["DATE_REF"])){
            $date_ref = date($params["DATE_REF"]);
        }

        $date_now = date("Y-m-d H:i:s");


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

            if(filter_var($_ENV["CHECK_PHP_TASKS_SYNTAX"], FILTER_VALIDATE_BOOLEAN)){
                $file_check_result = exec("php -l \"".$taskFile->getRealPath()."\"");
                if(strpos($file_check_result, 'No syntax errors detected in') === false){
                    //Syntax error in file
                    continue;
                }
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
                $row["last_outcome"] = '';

                if($calc_run_lst == "Y"){
                    $row["planned_in_interval"] = 0;
                    $row["executed_in_interval"] = 0;
                    $row["error_in_interval"] = 0;
                    $row["succesfull_in_interval"] = 0;

                    $row["interval_run_lst"] = [];
                    $row["executed_task_lst"] = [];
                    $row["outcome_executed_task_lst"] = [];
                }


                //Looking for all the logs related to this event
                $aLOGNAME = [];
                if(!empty($aLOGNAME_perkey[$row["event_unique_key"]])){
                    $aLOGNAME = $aLOGNAME_perkey[$row["event_unique_key"]]; //UNIQUE_KEY_OK_20191001100_20191001110.log | UNIQUE_KEY_KO_20191001100_20191001110.log
                }

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

                        if($calc_run > $interval_to) break;

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

                        if($calc_run < $interval_from) break;

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

                if(!empty($row["executed_task_lst"])){
                    $row["executed_last_run"] = array_key_last($row["executed_task_lst"]);
                }

                $row["last_run_actually_executed"] = false;
                $aLASTLOG = preg_grep( "#^$LOGS_DIR+\/".$row["event_unique_key"]."_[OK]{2}_".date("YmdHi", strtotime($row["calculeted_last_run"]))."_[0-9]{12}_[a-zA-Z0-9-]{4}.log$#", $aLOGNAME );

                if(!empty($aLASTLOG)){
                    $row["last_run_actually_executed"] = true;
                }


                //Calculating run list of the interval
                $calc_run = false;
                $tmp_interval_lst = [];
                $nincrement = 0;

                if($calc_run_lst == "Y"){

                    if($row["high_frequency"]){

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

                        foreach($row["executed_task_lst"] as $exec_task_start => $exec_task_end){
                            if($exec_task_start >= $event_interval_from_orig && $exec_task_start <= $event_interval_to && !array_key_exists($exec_task_start, $tmp_interval_lst)){
                                $tmp_interval_lst[$calc_run] = $calc_run;
                            }

                            if($exec_task_start >= $event_interval_from && $exec_task_start <= $event_interval_to && !array_key_exists($exec_task_start, $row["interval_run_lst"])){
                                $row["interval_run_lst"][$exec_task_start] = $exec_task_end;
                            }
                        }

                        ksort($row["interval_run_lst"]);

                        $row["planned_in_interval"] = count($tmp_interval_lst);
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

});
