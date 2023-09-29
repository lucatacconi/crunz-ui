<?php

require './vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

use Crunz\Configuration\Configuration;
use Crunz\Schedule;
use Crunz\Filesystem;
use Crunz\Task\Collection;
use Crunz\Task\WrongTaskInstanceException;



class TasksTreeReader {

    public static function getMaxNumTasks() {

        $crunz_base_dir = ".";

        if(!file_exists ( $crunz_base_dir."/crunz.yml" )) return false;
        $crunz_config_yml = file_get_contents($crunz_base_dir."/crunz.yml");


        if(empty($crunz_config_yml)) return false;

        try {
            $crunz_config = Yaml::parse($crunz_config_yml);
        } catch (ParseException $exception) {
            return false;
        }

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

            $event_file_id = 0;
            foreach ($aEVENTs as $oEVENT) {
                $task_counter++;
            }
        }

        //php -r 'include  "./TasksTreeReader.php"; $res = TasksTreeReader::getMaxNumTasks(); echo $res;'
        return $task_counter;
    }

    public static function getAllTree() {

        $crunz_base_dir = ".";

        if(!file_exists ( $crunz_base_dir."/crunz.yml" )) return false;
        $crunz_config_yml = file_get_contents($crunz_base_dir."/crunz.yml");


        if(empty($crunz_config_yml)) return false;

        try {
            $crunz_config = Yaml::parse($crunz_config_yml);
        } catch (ParseException $exception) {
            return false;
        }

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


        $aTASK = [];
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

            $event_file_id = 0;
            foreach ($aEVENTs as $oEVENT) {
                $row = [];
                $task_counter++;
                $event_file_id++;

                $row["filename"] = $taskFile->getFilename();
                $row["real_path"] = $taskFile->getRealPath();
                $row["subdir"] = str_replace( array( $TASKS_DIR, $row["filename"]),'',$row["real_path"]);
                $row["task_path"] = str_replace($TASKS_DIR, '', $row["real_path"]);

                $row["event_id"] = $oEVENT->getId();
                $row["event_launch_id"] = $task_counter;
                $row["event_file_id"] = $event_file_id;
                $row["task_description"] = $oEVENT->description;
                $row["expression"] = $oEVENT->getExpression();

                $row["event_unique_key"] = md5($row["task_path"] . $row["task_description"] . $row["expression"]);

                $aTASKs[$row["event_launch_id"]] = $row;
            }
        }

        return json_encode($aTASKs);
    }

    public static function getEventUniqueKey($event_launch_id_req = '') {

        if(empty($event_launch_id_req)){
            return false;
        }

        $crunz_base_dir = dirname(__FILE__);

        if(!file_exists ( $crunz_base_dir."/crunz.yml" )) return false;
        $crunz_config_yml = file_get_contents($crunz_base_dir."/crunz.yml");


        if(empty($crunz_config_yml)) return false;

        try {
            $crunz_config = Yaml::parse($crunz_config_yml);
        } catch (ParseException $exception) {
            return false;
        }

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


        $aTASK = [];
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

            $event_file_id = 0;
            foreach ($aEVENTs as $oEVENT) {
                $row = [];
                $task_counter++;
                $event_file_id++;

                if($task_counter == $event_launch_id_req){

                    $row["filename"] = $taskFile->getFilename();
                    $row["real_path"] = $taskFile->getRealPath();
                    $row["subdir"] = str_replace( array( $TASKS_DIR, $row["filename"]),'',$row["real_path"]);
                    $row["task_path"] = str_replace($TASKS_DIR, '', $row["real_path"]);

                    $row["event_id"] = $oEVENT->getId();
                    $row["event_launch_id"] = $task_counter;
                    $row["event_file_id"] = $event_file_id;
                    $row["task_description"] = $oEVENT->description;
                    $row["expression"] = $oEVENT->getExpression();

                    $row["event_unique_key"] = md5($row["task_path"] . $row["task_description"] . $row["expression"]);

                    $aTASK = $row;
                    break(2);
                }
            }
        }

        if(empty($aTASK) || empty($aTASK["event_unique_key"])) return false;
        return $aTASK["event_unique_key"];
    }
}

