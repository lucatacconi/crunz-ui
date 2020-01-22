<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


$app->group('/environment', function () use ($app) {

    $app->post('/check', function ($request, $response, $args) {

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




        return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    });
});
