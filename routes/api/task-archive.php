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

    $forced_task_path = '';

    $group->get('/', function (Request $request, Response $response, array $args) use($forced_task_path) {


        //Parameters list
        // RETURN_TASK_CONT - Y | N - set API to show content of the task (PHP code)
        // TASK_PATH - path - Select task by path
        // UNIQUE_ID - id - Select task by Unique ID

        $data = [];

        $params = array_change_key_case($request->getQueryParams(), CASE_UPPER);

        $date_now = date("Y-m-d H:i:s");

        $return_task_content = "N";
        if(!empty($params["RETURN_TASK_CONT"])){
            $return_task_content = $params["RETURN_TASK_CONT"];
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
        if(empty($crunz_config["timezone"])) throw new Exception("ERROR - Wrong timezone configuration");

        date_default_timezone_set($crunz_config["timezone"]);

        if(empty($forced_task_path)){
            $TASKS_DIR = $crunz_base_dir . "/" . ltrim($crunz_config["source"], "/");
        }else{
            $TASKS_DIR = $forced_task_path;
        }

        $TASK_SUFFIX = str_replace(".php", ".arch", $crunz_config["suffix"]);

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

        $aARCHs = [];
        $arch_counter = 0;

        foreach ($files as $archFile) {

            $file_content_check = $file_content_orig = file_get_contents($archFile->getRealPath(), true);
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

            if(is_callable('shell_exec') && false === stripos(ini_get('disable_functions'), 'shell_exec')){
                if(filter_var($_ENV["CHECK_PHP_TASKS_SYNTAX"], FILTER_VALIDATE_BOOLEAN)){
                    $file_check_result = exec("php -l \"".$archFile->getRealPath()."\"");
                    if(strpos($file_check_result, 'No syntax errors detected in') === false){
                        //Syntax error in file
                        continue;
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
                    continue;
                }
            }


            unset($schedule);
            require $archFile->getRealPath();
            if (empty($schedule) || !$schedule instanceof Schedule) {
                continue;
            }

            $aEVENTs = $schedule->events();

            foreach ($aEVENTs as $oEVENT) {
                $row = [];

                $row["filename"] = $archFile->getFilename();
                $row["real_path"] = $archFile->getRealPath();
                $row["storage_datetime"] = date ("Y-m-d H:i:s", filemtime($row["real_path"]));
                $row["subdir"] = str_replace( array( $TASKS_DIR, $row["filename"]),'',$row["real_path"]);
                $row["task_path"] = str_replace($TASKS_DIR, '', $row["real_path"]);

                if(!empty($params["TASK_PATH"])){
                    if($row["task_path"] != $params["TASK_PATH"]){
                        continue;
                    }
                }

                $row["event_id"] = $oEVENT->getId();
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
                $life_datetime_from = '';
                $life_datetime_to = '';

                $delimiter = '#';
                $startTag = '->between(';
                $endTag = ')';
                $regex = $delimiter . preg_quote($startTag, $delimiter)
                                    . '(.*?)'
                                    . preg_quote($endTag, $delimiter)
                                    . $delimiter
                                    . 's';
                preg_match($regex, $file_content_check, $matches);
                if(!empty($matches) and strpos($matches[1], ',') !== false){
                    $aTIMELIFE = explode(",", $matches[1]);
                    $life_datetime_from = strtotime( str_replace(array("'", "\""), '', $aTIMELIFE[0] ));
                    $life_datetime_to = strtotime( str_replace(array("'", "\""), '', $aTIMELIFE[1] ));
                }

                if(empty($life_datetime_from)){
                    $delimiter = '#';
                    $startTag = '->from(';
                    $endTag = ')';
                    $regex = $delimiter . preg_quote($startTag, $delimiter)
                                        . '(.*?)'
                                        . preg_quote($endTag, $delimiter)
                                        . $delimiter
                                        . 's';
                    preg_match($regex, $file_content_check, $matches);
                    if(!empty($matches)){
                        $life_datetime_from = strtotime( str_replace(array("'", "\""), '', $matches[1] ));
                    }
                }

                if(empty($life_datetime_to)){
                    $delimiter = '#';
                    $startTag = '->to(';
                    $endTag = ')';
                    $regex = $delimiter . preg_quote($startTag, $delimiter)
                                        . '(.*?)'
                                        . preg_quote($endTag, $delimiter)
                                        . $delimiter
                                        . 's';
                    preg_match($regex, $file_content_check, $matches);
                    if(!empty($matches)){
                        $life_datetime_to = strtotime( str_replace(array("'", "\""), '', $matches[1] ));
                    }
                }

                if(!empty($life_datetime_from)){
                    $row["life_datetime_from"] = date('Y-m-d H:i:s', $life_datetime_from);
                }
                if(!empty($life_datetime_to)){
                    $row["life_datetime_to"] = date('Y-m-d H:i:s', $life_datetime_to);
                }

                if(!empty($row["life_datetime_from"]) || !empty($row["life_datetime_to"])){

                    $lifetime_descr = " (Executed";

                    if(!empty($row["life_datetime_from"])){
                        $lifetime_descr .= " from ".$row["life_datetime_from"];
                    }

                    if(!empty($row["life_datetime_to"])){
                        $lifetime_descr .= " to ".$row["life_datetime_to"];
                    }

                    $lifetime_descr .= ")";

                    $row["task_description"] = $row["task_description"].$lifetime_descr;
                }

                try {
                    $row["expression_readable"] = CronTranslator::translate($row["expression"]);
                } catch (Exception $e) {
                    $row["expression_readable"] = "";
                }

                $row["commnad"] = $oEVENT->getCommandForDisplay();

                if(substr($row["expression"], 0, 3) == '* *' && substr($row["expression"], 4) != '* * *'){
                    $row["expression"] = '0 0'.substr($row["expression"],3);
                }


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

                preg_match($regex, $file_content_check, $matches);
                if(!empty($matches) && empty($custom_log)){
                    $custom_log = str_replace(array("'", "\""), '', $matches[1] );
                }

                preg_match($regex2, $file_content_check, $matches);
                if(!empty($matches) && empty($custom_log)){
                    $custom_log = str_replace(array("'", "\""), '', $matches[1] );
                }

                if($return_task_content == "Y"){
                    $row["task_content"] = base64_encode($file_content_orig);
                }

                $row["custom_log"] = $custom_log;

                $aARCHs[] = $row;

                if(!empty($params["UNIQUE_ID"])){
                    if($row["event_unique_key"] == $params["UNIQUE_ID"]){
                        break;
                    }
                }
            }
        };

        $data = $aARCHs;

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });

    $group->post('/archive', function (Request $request, Response $response, array $args) use($forced_task_path) {

        $data = [];

        $params = [];
        if(!empty($request->getParsedBody())){
            $params = array_change_key_case($request->getParsedBody(), CASE_UPPER);
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

        if(empty($forced_task_path)){
            $TASKS_DIR = $crunz_base_dir . "/" . ltrim($crunz_config["source"], "/");
        }else{
            $TASKS_DIR = $forced_task_path;
        }

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

            $out = copy($file_to_archive, str_replace(".php", ".arch", $file_to_archive));
            if(!$out){
                throw new Exception('ERROR - Archive error');
            }

            unlink($file_to_archive);

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


    $group->post('/de-archive', function (Request $request, Response $response, array $args) use($forced_task_path) {

        $data = [];

        $params = [];
        if(!empty($request->getParsedBody())){
            $params = array_change_key_case($request->getParsedBody(), CASE_UPPER);
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

        if(empty($forced_task_path)){
            $TASKS_DIR = $crunz_base_dir . "/" . ltrim($crunz_config["source"], "/");
        }else{
            $TASKS_DIR = $forced_task_path;
        }

        $TASK_SUFFIX_DEST = $crunz_config["suffix"];
        $TASK_SUFFIX = str_replace(".php", ".arch", $crunz_config["suffix"]);

        if(!is_writable($TASKS_DIR)) throw new Exception('ERROR - Tasks directory not writable');

        $base_tasks_path = $TASKS_DIR; //Must be absolute path on server

        if(empty($params["ARCH_PATH"])) throw new Exception("ERROR - No task file to archive submitted");

        //File compliance check
        $path_check = str_replace(".arch", '', $params["TASK_PATH"]);

        if(
            strpos($path_check, '.') !== false ||
            substr($params["ARCH_PATH"], - strlen($TASK_SUFFIX)) != $TASK_SUFFIX ||
            strpos($path_check, $TASKS_DIR === false)
        ){
            throw new Exception("ERROR - Task path out of range");
        }

        $data["path"] = $params["ARCH_PATH"];
        $file_to_archive = $base_tasks_path."/".ltrim($params["ARCH_PATH"],"/");

        try {

            if(!file_exists($file_to_archive)) throw new Exception('ERROR - File not present');

            if(!is_writable($file_to_archive)) throw new Exception('ERROR - File not writable');

            $out = copy($file_to_archive, str_replace(".arch", ".php", $file_to_archive));
            if(!$out){
                throw new Exception('ERROR - De-archive error');
            }

            unlink($file_to_archive);

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
