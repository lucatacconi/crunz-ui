<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;
use Tuupola\Base62;

use Symfony\Component\Yaml\Yaml;

$app->group('/environment', function () use ($app) {

    $app->get('/check', function ($request, $response, $args) {

        $data = [];

        $params = array_change_key_case($request->getParams(), CASE_UPPER);

        $app_configs = $this->get('app_configs');
        $base_path =$app_configs["paths"]["base_path"];

        if(empty(getenv("CRUNZ_BASE_DIR"))){
            $crunz_base_dir = $base_path;
            $data["TASK_POSITION_EMBEDDED"] = true;
            $data["TASK_DIR"] = '';
        }else{
            $crunz_base_dir = getenv("CRUNZ_BASE_DIR");
            $data["TASK_POSITION_EMBEDDED"] = false;
            $data["TASK_DIR"] = $crunz_base_dir;
        }

        $data["YAML_CONFIG_PRESENCE"] = false;
        $data["YAML_CONFIG_NOEMPTY"] = false;
        $data["YAML_CONFIG_CORRECTNESS"] = false;
        $data["YAML_CONFIG_SOURCE_PRESENCE"] = false;
        $data["YAML_CONFIG_SOURCE"] = false;
        $data["YAML_CONFIG_SUFFIX_PRESENCE"] = false;
        $data["YAML_CONFIG_SUFFIX"] = false;
        $data["YAML_CONFIG_TIMEZONE_PRESENCE"] = false;
        $data["TIMEZONE_CONFIG"] = '';
        $data["TASKS_DIR_PRESENCE"] = false;
        $data["TASKS_DIR_WRITABLE"] = false;
        $data["LOGS_DIR_CONFIG_PRESENCE"] = false;
        $data["LOGS_DIR_PRESENCE"] = false;
        $data["LOGS_DIR_WRITABLE"] = false;

        //===================================================================================================================================================

        if(file_exists ( $crunz_base_dir."/crunz.yml" )){
            $data["YAML_CONFIG_PRESENCE"] = true;

            $crunz_config_yml = file_get_contents($crunz_base_dir."/crunz.yml");

            if(!empty($crunz_config_yml)){
                $data["YAML_CONFIG_NOEMPTY"] = true;

                try {
                    $crunz_config = Yaml::parse($crunz_config_yml);
                    $data["YAML_CONFIG_CORRECTNESS"] = true;
                } catch (ParseException $exception) {
                    $data["YAML_CONFIG_CORRECTNESS"] = false;
                }

                if($data["YAML_CONFIG_CORRECTNESS"]){

                    if(!empty($crunz_config["source"])){
                        $data["YAML_CONFIG_SOURCE_PRESENCE"] = true;
                        $data["YAML_CONFIG_SOURCE"] = $crunz_config["source"];
                    }
                    if(!empty($crunz_config["suffix"])){
                        $data["YAML_CONFIG_SUFFIX_PRESENCE"] = true;
                        $data["YAML_CONFIG_SUFFIX"] = $crunz_config["suffix"];
                    }
                    if(!empty($crunz_config["timezone"])){
                        $data["YAML_CONFIG_TIMEZONE_PRESENCE"] = true;
                    }

                    date_default_timezone_set($crunz_config["timezone"]);
                    $data["TIMEZONE_CONFIG"] = $crunz_config["timezone"];

                    $TASKS_DIR = $crunz_base_dir . "/" . ltrim($crunz_config["source"], "/");
                    $TASK_SUFFIX = $crunz_config["suffix"];

                    if(is_dir($TASKS_DIR)){
                        $data["TASKS_DIR_PRESENCE"] = true;

                        if(is_writable($TASKS_DIR)){
                            $data["TASKS_DIR_WRITABLE"] = true;
                        }
                    }

                    if(!empty(getenv("LOGS_DIR"))){
                        $data["LOGS_DIR_CONFIG_PRESENCE"] = true;

                        if(substr(getenv("LOGS_DIR"), 0, 2) == "./"){
                            $LOGS_DIR = $base_path . "/" . getenv("LOGS_DIR");
                        }else{
                            $LOGS_DIR = getenv("LOGS_DIR");
                        }

                        if(is_dir($LOGS_DIR)){
                            $data["LOGS_DIR_PRESENCE"] = true;

                            if(is_writable($LOGS_DIR)){
                                $data["LOGS_DIR_WRITABLE"] = true;
                            }
                        }
                    }
                }
            }
        }

        return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    });
});
