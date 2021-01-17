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

        $aSTATs = [];
        $data_calc = substr($interval_from, 0, 10);

        while($data_calc <= substr($interval_to, 0, 10)){
            if(empty($aSTATs[$data_calc])){
                $aSTATs[$data_calc] = array( "planned" => 0, "executed" => 0, "error" => 0, "succesfull" => 0);
            }

            $data_calc = date('Y-m-d', strtotime("$data_calc + 1 days"));
        }

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

                $event_file_id++;

                $event_interval_from = $interval_from;
                $event_interval_to = $interval_to;

                $real_path = $taskFile->getRealPath();
                $task_description = $taskFile->getRealPath();
                $expression = $oEVENT->getExpression();

                $event_unique_key = md5($real_path . $task_description . $expression);

                //Check task if it is high_frequency task (more then once an hour)
                $aEXPRESSION = explode(" ", $oEVENT->getExpression());
                $high_frequency = false;

                if( $aEXPRESSION[0] == '*' || strpos($aEXPRESSION[0], "-") !== false || strpos($aEXPRESSION[0], ",") !== false || strpos($aEXPRESSION[0], "/") !== false ){
                    $high_frequency = true;
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

                $event_interval_from_orig = $event_interval_from;
                $event_interval_to_orig = $event_interval_to;

                unset($cron);
                $cron = Cron\CronExpression::factory($expression);


                $aLOGNAME = glob($LOGS_DIR."/".$event_unique_key."*.log"); //UNIQUE_KEY_OK_20191001100_20191001110.log | UNIQUE_KEY_KO_20191001100_20191001110.log

                if(!empty($aLOGNAME)){
                    usort( $aLOGNAME, function( $a, $b ) { return filemtime($b) - filemtime($a); } );

                    //0 UNIQUE_KEY
                    //1 Outcome
                    //2 Start datetime
                    //3 End datetime

                    $aLASTLOG =explode('_', str_replace($LOGS_DIR."/", "", $aLOGNAME[0]));

                    foreach( $aLOGNAME as $aLOGNAME_key => $LOGFOCUS ){
                        $aLOGFOCUS =explode('_', str_replace($LOGS_DIR."/", "", $LOGFOCUS));
                        $task_start = DateTime::createFromFormat('YmdHi', $aLOGFOCUS[2]);
                        $task_stop = DateTime::createFromFormat('YmdHi', $aLOGFOCUS[3]);

                        if($task_start->format('Y-m-d H:i:s') < $event_interval_from || $task_start->format('Y-m-d H:i:s') > $event_interval_to){
                            continue;
                        }

                        $aSTATs[$task_start->format('Y-m-d')]["executed"]++;
                        if($aLOGFOCUS[1] == "OK"){
                            $aSTATs[$task_start->format('Y-m-d')]["succesfull"]++;
                        }else{
                            $aSTATs[$task_start->format('Y-m-d')]["error"]++;
                        }
                    }


                    $aFIRSTLOG = explode('_', str_replace($LOGS_DIR."/", "", end($aLOGNAME)));
                    $task_start = DateTime::createFromFormat('YmdHi', $aFIRSTLOG[2]);
                }

                //Calculating run list of the interval
                $calc_run = false;
                $tmp_interval_lst = [];
                $nincrement = 0;

                if($high_frequency){

                    $nincrement++;

                    do{

                        if(empty($calc_run_ref)){
                            $calc_run_ref = $event_interval_from_orig;
                        }else{
                            $calc_run_ref = $cron->getNextRunDate($calc_run_ref, $nincrement, true)->format('Y-m-d H:i:s');
                        }

                        if($calc_run_ref <= $event_interval_to){
                            $aSTATs[substr($calc_run_ref, 0, 10)]["planned"]++;
                        }

                    } while(empty($calc_run_ref) || $calc_run_ref < $event_interval_to);

                }else{

                    while($nincrement < 1000){ //Use the same hard limit of cron-expression library
                        $calc_run = $cron->getNextRunDate($event_interval_from_orig, $nincrement, true)->format('Y-m-d H:i:s');

                        if($calc_run > $event_interval_to){
                            break;
                        }

                        $nincrement++;

                        $aSTATs[substr($calc_run, 0, 10)]["planned"]++;
                    }

                    // foreach($row["executed_task_lst"] as $exec_task_start => $exec_task_end){
                    //     if($exec_task_start >= $event_interval_from_orig && $exec_task_start <= $event_interval_to && !array_key_exists($exec_task_start, $tmp_interval_lst)){
                    //         $tmp_interval_lst[$calc_run] = $calc_run;
                    //     }
                    // }
                }
            }
        }

        $data = $aSTATs;

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });
});
