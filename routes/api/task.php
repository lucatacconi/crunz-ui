<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;
use Tuupola\Base62;

use Crunz\Configuration\Configuration;
use Crunz\Schedule;
use Crunz\Task\Collection;
use Crunz\Task\WrongTaskInstanceException;

foreach (glob(__DIR__ . '/../classes/*.php') as $filename){
    require_once $filename;
}

use CrunzUI\Task\CrunzUITaskGenerator;

$app->group('/task', function () use ($app) {

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
        }

        $interval_to = date("Y-m-t 23:59:59");
        if(!empty($params["INTERVAL_TO"])){
            $interval_to = date($params["INTERVAL_TO"]);
        }


        if(empty(getenv("TASK_DIR"))) throw new Exception("ERROR - Tasks directory configuration empty");
        if(empty(getenv("TASK_SUFFIX"))) throw new Exception("ERROR - Wrong tasks configuration");

        $pathtotasks = '..'.getenv("TASK_DIR");

        $directoryIterator = new \RecursiveDirectoryIterator($pathtotasks);
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
                $row["pathname"] = $taskFile->getPathname();
                $row["realpath"] = $taskFile->getRealPath();
                $row["task_decription"] = $oEVENT->description;
                $row["subdir"] = str_replace( array($pathtotasks, $row["filename"]),'',$taskFile->getPathname());
                $row["expression"] = $oEVENT->getExpression();
                //$row["commnad"] = $oEVENT->getCommandForDisplay();


                $file_content = file_get_contents($taskFile->getRealPath(), true);
                $file_content = str_replace(array(" ","\t","\n","\r"), '', $file_content);

                $task_configuration = '';
                $start_pos = strpos($file_content, '$task->');
                $end_pos = strpos($file_content, ');', $start_pos);

                if ($start_pos === false || $end_pos === false){
                    throw new Exception("ERROR - Wrong tasks file format");
                }

                $task_configuration = substr($file_content, $start_pos+1, ($end_pos+1)-$start_pos);
                $task_configuration = preg_replace('(\->description.*?\'\))', '', $task_configuration);
                $task_configuration = str_replace("//", '', $task_configuration);
                $row["task_configuration"] = $task_configuration;

                if(substr($row["expression"], 0, 3) == '* *' && substr($row["expression"], 4) != '* * *'){
                    $row["expression"] = '0 0'.substr($row["expression"],3);
                }

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

                //hourly
                //daily
                //weekly
                //weeklyOn
                //monthly
                //quarterly
                //yearly
                //( at midnight)
                //^every([A-Z][a-zA-Z]+)?(Minute|Hour|Day|Month)s?$/

                //dailyAt

                //on / at()
                // on('13:30'); at('13:30'); on('13:30 2016-03-01');

                //twiceDaily
                //weekdays
                //mondays
                //tuesdays
                //wednesdays
                //thursdays
                //fridays
                //saturdays
                //sundays

                //between
                //from
                //to

                //days
                //hour
                //minute
                //dayOfMonth
                //month
                //dayOfWeek

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

        $params = array_change_key_case($request->getParams(), CASE_UPPER);

        if(empty(getenv("TASK_DIR"))) throw new Exception("ERROR - Tasks directory configuration empty");
        if(empty(getenv("TASK_SUFFIX"))) throw new Exception("ERROR - Wrong tasks configuration");

        if(empty(getenv("TASK_NAME"))) throw new Exception("ERROR - Wrong tasks configuration");
        if(empty(getenv("SUBDIR"))) throw new Exception("ERROR - Wrong tasks configuration");
        if(empty(getenv("TASK_DECRIPTION"))) throw new Exception("ERROR - Wrong tasks configuration");
        if(empty(getenv("STATUS"))) throw new Exception("ERROR - Wrong tasks configuration");
        if(empty(getenv("COMMAND"))) throw new Exception("ERROR - Wrong tasks configuration");








        $test = new \CrunzUI\Task\CrunzUITaskGenerator();



        print_r($test);
        die();

        $data = [];

        $data[] = __DIR__;

        //$data = $aTASKs;

        return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    });

    $app->post('/upload', function ($request, $response, $args) {

        $data = [];

        $params = array_change_key_case($request->getParams(), CASE_UPPER);






        $test = new \CrunzUI\Task\CrunzUITaskGenerator();



        print_r($test);
        die();

        $data = [];

        $data[] = __DIR__;

        //$data = $aTASKs;

        return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    });


    $app->delete('/', function ($request, $response, $args) {

        $data = [];

        $params = array_change_key_case($request->getParams(), CASE_UPPER);

        if(empty(getenv("TASK_DIR"))) throw new Exception("ERROR - Tasks directory configuration empty");
        if(empty(getenv("TASK_SUFFIX"))) throw new Exception("ERROR - Wrong tasks configuration");

        if(empty($params["TASK_PATH"])) throw new Exception("ERROR - No task file to delete submitted");

        if(
            substr($params["TASK_PATH"], -strlen(getenv("TASK_SUFFIX"))) != getenv("TASK_SUFFIX") ||
            strpos($params["TASK_PATH"], getenv("TASK_DIR") === false)
        ){
            if(empty($params["TASK_PATH"])) throw new Exception("ERROR - Task path out of range");
        }

        $data["path"] = $params["TASK_PATH"];

        try {
            if(!is_writable($params["TASK_PATH"])) throw new Exception('ERROR - File not writable');

            unlink($params["TASK_PATH"]);

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
