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


$app->group('/task', function () use ($app) {

    $app->get('/', function ($request, $response, $args) {

        $data = [];

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


                $file_content = file_get_contents($taskFile->getRealPath(), true);
                $file_content = str_replace(array(" ","\t","\n","\r"), '', $file_content);

                $task_configuration = '';
                $start_pos = strpos($file_content, '$task->');
                $end_pos = strpos($file_content, ');', $start_pos);
                $task_configuration = substr($file_content, $start_pos+1, ($end_pos+1)-$start_pos);


                $row["task_configuration"] = $task_configuration;

                unset($cron);
                $cron = Cron\CronExpression::factory($row["expression"]);
                $row["next_run"] = $cron->getNextRunDate()->format('Y-m-d H:i:s');




                $row["next_run_lst"] = [];
                $aRUNs = $cron->getMultipleRunDates(30, date("Y-m-01 00:00:00"), false, true);
                foreach($aRUNs as $aRUN_key => $aRUN){
                    $row["next_run_lst"][] = $aRUN->format('Y-m-d H:i:s');
                }




                //$row["next_run_lst"][] = $cron->getMultipleRunDates(30, date("Y-m-01 00:00:00"), false, true);



                // $cron = Cron\CronExpression::factory('@daily');
                // $cron->isDue();
                // die ($cron->getNextRunDate()->format('Y-m-d H:i:s'));


                // $start_pos = strpos($haystack,$start_limiter);
                // if ($start_pos === FALSE)
                // {
                //     return FALSE;
                // }

                // $end_pos = strpos($haystack,$end_limiter,$start_pos);

                // if ($end_pos === FALSE)
                // {
                //    return FALSE;
                // }

                // return substr($haystack, $start_pos+1, ($end_pos-1)-$start_pos);

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

});
