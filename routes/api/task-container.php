<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;
use Symfony\Component\Yaml\Yaml;
use CrunzUI\Tools\CrunzUITools;

$app->group('/task-container', function (RouteCollectorProxy $group) {

    $group->get('/tree', function (Request $request, Response $response, array $args) {

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

        $TASKS_DIR = $crunz_base_dir . "/" . ltrim($crunz_config["source"], "/");

        $data = CrunzUITools::taskDirectoryRotator($TASKS_DIR);

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });

    $group->post('/dir', function (Request $request, Response $response, array $args) {

        $data = [];

        $params = array_change_key_case($request->getQueryParams(), CASE_UPPER);

        if(empty($params["DIR_NAME"])) throw new Exception("ERROR - Missing name of the directory being added");

        $params["DIR_NAME"] = str_replace(['.'], '', $params["DIR_NAME"]);

        if($params["DIR_NAME"] == "/") throw new Exception("ERROR - Directory being added can not be main path");


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

        $TASKS_DIR = $crunz_base_dir . "/" . ltrim($crunz_config["source"], "/");

        $aPATH = CrunzUITools::taskDirectoryRotator($TASKS_DIR);

        try {

            if(strlen($params["DIR_NAME"]) > 1){
                $params["DIR_NAME"] = rtrim($params["DIR_NAME"],"/");
            }

            $aDESTINATION = explode("/", $params["DIR_NAME"]);
            array_pop($aDESTINATION);

            $dest_up1 = '';
            foreach($aDESTINATION as $row_cnt => $row_data){
                if(!empty($row_data)){
                    $dest_up1 .= '/' . $row_data;
                }else{
                    $dest_up1 = '/';
                }
            }

            $dest_up1 = str_replace("//", "/", $dest_up1);

            if(!in_array($dest_up1, $aPATH) ){
                throw new Exception("ERROR - Directory parent not present");
            }

            if(!is_writable($TASKS_DIR  . $dest_up1)) throw new Exception('ERROR - Directory parent not writable');

            if(is_dir( $TASKS_DIR  . $params["DIR_NAME"] )){
                throw new Exception("ERROR - Directory being added already present");
            }

            if (!mkdir( $TASKS_DIR  . rtrim($params["DIR_NAME"],"/") )) {
                throw new Exception("ERROR - Failed to create folders...");
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

    $group->delete('/dir', function (Request $request, Response $response, array $args) {

        $data = [];

        $params = array_change_key_case($request->getQueryParams(), CASE_UPPER);

        if(empty($params["DIR_NAME"])) throw new Exception("ERROR - Missing name of the directory being deleted");

        $params["DIR_NAME"] = str_replace(['.'], '', $params["DIR_NAME"]);

        if($params["DIR_NAME"] == "/") throw new Exception("ERROR - Directory being deleted can not be main path");


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

        $TASKS_DIR = $crunz_base_dir . "/" . ltrim($crunz_config["source"], "/");

        try {

            $dir_delete = $TASKS_DIR . $params["DIR_NAME"];

            $aCONTENT = scandir($dir_delete);
            if(count($aCONTENT) > 2) throw new Exception("ERROR - Directory being deleted not empty");

            if(!is_writable($dir_delete)) throw new Exception('ERROR - Directory being deleted not writable');

            if(!rmdir($dir_delete)) {
                throw new Exception('ERROR - Could not remove directory');
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
