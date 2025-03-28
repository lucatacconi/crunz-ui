<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;

use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;
use Tuupola\Base62;

use Symfony\Component\Yaml\Yaml;

$app->group('/environment', function (RouteCollectorProxy $group) {

    $forced_task_path = '';

    $group->get('/crunz-default-config', function (Request $request, Response $response, array $args) {

        $data = [];

        $params = array_change_key_case($request->getQueryParams(), CASE_UPPER);

        $app_configs = $this->get('configs')["app_configs"];
        $base_path =$app_configs["paths"]["base_path"];

        if(empty($_ENV["CRUNZ_BASE_DIR"])){
            $crunz_base_dir = $base_path;
        }else{
            $crunz_base_dir = $_ENV["CRUNZ_BASE_DIR"];
        }

        if(!file_exists ( $crunz_base_dir."/crunz.yml.example" )) throw new Exception("ERROR - Crunz.yml example configuration file not found");
        $crunz_config_yml = file_get_contents($crunz_base_dir."//crunz.yml.example");

        if(empty($crunz_config_yml)) throw new Exception("ERROR - Crunz example configuration file empty");

        try {
            $crunz_config = Yaml::parse($crunz_config_yml);
        } catch (ParseException $exception) {
            throw new Exception("ERROR - Crunz  example configuration file error");
        }

        $data = $crunz_config;

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });

    $group->get('/crunz-config', function (Request $request, Response $response, array $args) {

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

        $data = $crunz_config;

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });

    $group->post('/crunz-config', function (Request $request, Response $response, array $args) {

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

        $crunz_config_path = $base_path."/crunz.yml";

        if(!file_exists($crunz_config_path)){
            if(!is_writable($base_path)) throw new Exception('ERROR - Config file path not writable');
        }else{
            if(!is_writable($crunz_config_path)) throw new Exception('ERROR - Config file not writable');
        }

        if(empty($params["CONFIG_CONTENT"])) throw new Exception("ERROR - Crunz configuration empty");

        $crunz_config_content = base64_decode($params["CONFIG_CONTENT"]);

        if(empty($crunz_config_content)) throw new Exception("ERROR - Crunz configuration empty");


        $crunz_config_handle = fopen($crunz_config_path, "w");
        if($crunz_config_handle === false) throw new Exception('ERROR - Error in opening config file');

        fwrite($crunz_config_handle, $crunz_config_content);
        fclose($crunz_config_handle);


        $data["result"] = true;
        $data["result_msg"] = '';


        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });

    $group->get('/users-config', function (Request $request, Response $response, array $args) {

        $data = [];

        $params = array_change_key_case($request->getQueryParams(), CASE_UPPER);

        $app_configs = $this->get('configs')["app_configs"];
        $base_path =$app_configs["paths"]["base_path"];

        if(empty($_ENV["CRUNZ_BASE_DIR"])){
            $crunz_base_dir = $base_path;
        }else{
            $crunz_base_dir = $_ENV["CRUNZ_BASE_DIR"];
        }

        if(!file_exists ( $crunz_base_dir."/config/accounts.json" )) throw new Exception("ERROR - Users configuration not found");
        $users_config = file_get_contents($crunz_base_dir."/config/accounts.json");

        if(empty($users_config)) throw new Exception("ERROR - Users configuration empty");

        $data = json_decode($users_config, true);

        if(is_null($data)){
            throw new Exception("ERROR - Users configuration error");
        }

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });

    $group->post('/users-config', function (Request $request, Response $response, array $args) {

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

        $users_config_path = $base_path."/config/accounts.json";

        if(!file_exists($users_config_path)){
            throw new Exception('ERROR - Users configuration not exist');
        }

        if(!is_writable($users_config_path)){
            throw new Exception('ERROR - Users configuration not writable');
        }

        if(empty($params["CONFIG_CONTENT"])) throw new Exception("ERROR - Users configuration empty");

        $users_config_content = base64_decode($params["CONFIG_CONTENT"]);

        if(empty($users_config_content)) throw new Exception("ERROR - Users configuration empty");


        $crunz_config_handle = fopen($users_config_path, "w");
        if($crunz_config_handle === false) throw new Exception('ERROR - Users in opening config file');

        fwrite($crunz_config_handle, $users_config_content);
        fclose($crunz_config_handle);


        $data["result"] = true;
        $data["result_msg"] = '';


        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });

    $group->get('/check', function (Request $request, Response $response, array $args) use($forced_task_path) {

        $data = [];

        $params = array_change_key_case($request->getQueryParams(), CASE_UPPER);

        $app_configs = $this->get('configs')["app_configs"];
        $base_path =$app_configs["paths"]["base_path"];

        if(empty($_ENV["CRUNZ_BASE_DIR"])){
            $crunz_base_dir = $base_path;
            $data["TASK_POSITION_EMBEDDED"] = true;
            $data["TASK_DIR"] = '';
        }else{
            $crunz_base_dir = $_ENV["CRUNZ_BASE_DIR"];
            $data["TASK_POSITION_EMBEDDED"] = false;
            $data["TASK_DIR"] = $crunz_base_dir;
        }

        $data["CRUNZ_SH_PRESENCE"] = false;
        $data["TREEREADER_PRESENCE"] = false;

        if (is_file($crunz_base_dir . "/crunz-ui.sh")) {
            $data["CRUNZ_SH_PRESENCE"] = true;
        }
        if (is_file($crunz_base_dir . "/TasksTreeReader.php")) {
            $data["TREEREADER_PRESENCE"] = true;
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
        $data["TASKS_DIR"] = '';
        $data["TASKS_DIR_PRESENCE"] = false;
        $data["TASKS_DIR_WRITABLE"] = false;
        $data["LOGS_DIR"] = '';
        $data["LOGS_DIR_CONFIG_PRESENCE"] = false;
        $data["LOGS_DIR_PRESENCE"] = false;
        $data["LOGS_DIR_WRITABLE"] = false;
        $data["SHELL_EXEC_CAPABILITY"] = false;
        $data["ALL_CHECK"] = false;

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

                    if(empty($forced_task_path)){
                        $TASKS_DIR = $crunz_base_dir . "/" . ltrim($crunz_config["source"], "/");
                    }else{
                        $TASKS_DIR = $forced_task_path;
                    }

                    $TASK_SUFFIX = $crunz_config["suffix"];

                    $data["TASKS_DIR"] = $TASKS_DIR;

                    if(is_dir($TASKS_DIR)){
                        $data["TASKS_DIR_PRESENCE"] = true;

                        if(is_writable($TASKS_DIR)){
                            $data["TASKS_DIR_WRITABLE"] = true;
                        }
                    }
                }
            }
        }

        if(!empty($_ENV["LOGS_DIR"])){
            $data["LOGS_DIR_CONFIG_PRESENCE"] = true;

            if(substr($_ENV["LOGS_DIR"], 0, 2) == "./"){
                $LOGS_DIR = $base_path . "/" . $_ENV["LOGS_DIR"];
            }else{
                $LOGS_DIR = $_ENV["LOGS_DIR"];
            }

            $data["LOGS_DIR"] = $LOGS_DIR;

            if(is_dir($LOGS_DIR)){
                $data["LOGS_DIR_PRESENCE"] = true;

                if(is_writable($LOGS_DIR)){
                    $data["LOGS_DIR_WRITABLE"] = true;
                }
            }
        }

        if(is_callable('shell_exec') && false === stripos(ini_get('disable_functions'), 'shell_exec')){
            $data["SHELL_EXEC_CAPABILITY"] = true;
        }else{
            $data["SHELL_EXEC_CAPABILITY"] = false;
        }

        if(
            $data["YAML_CONFIG_PRESENCE"] == true &&
            $data["YAML_CONFIG_NOEMPTY"] == true &&
            $data["YAML_CONFIG_CORRECTNESS"] == true &&
            $data["YAML_CONFIG_SOURCE_PRESENCE"] == true &&
            $data["YAML_CONFIG_SUFFIX_PRESENCE"] == true &&
            $data["YAML_CONFIG_TIMEZONE_PRESENCE"] == true &&
            $data["TASKS_DIR_PRESENCE"] == true &&
            $data["TASKS_DIR_WRITABLE"] == true &&
            $data["LOGS_DIR_CONFIG_PRESENCE"] == true &&
            $data["LOGS_DIR_PRESENCE"] == true &&
            $data["LOGS_DIR_WRITABLE"] == true &&
            $data["CRUNZ_SH_PRESENCE"] == true &&
            $data["TREEREADER_PRESENCE"] == true
        ){
            $data["ALL_CHECK"] = true;
        }

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });
});
