<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;
use Symfony\Component\Yaml\Yaml;

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



        $dir_rotator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($TASKS_DIR));

        $aPATH = [];
        foreach ($dir_rotator as $path) {

            if (!$path->isDir()){
                continue;
            }

            $path = str_replace(['.','/.','/.','/..', $TASKS_DIR], '', $path);

            if(!in_array($path, $aPATH)){
                $aPATH[] = $path;
            }
        }

        $data = $aPATH;

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });

    $group->post('/dir', function (Request $request, Response $response, array $args) {

        $data = [];

        $params = array_change_key_case($request->getQueryParams(), CASE_UPPER);

        if(empty($_ENV["DIR_NAME"])) throw new Exception("ERROR - New directory name empty");


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



        $dir_rotator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($TASKS_DIR));

        $aPATH = [];
        foreach ($dir_rotator as $path) {

            if (!$path->isDir()){
                continue;
            }

            $path = str_replace(['.','/.','/.','/..', $TASKS_DIR], '', $path);

            if(!in_array($path, $aPATH)){
                $aPATH[] = $path;
            }
        }





        $data = $aPATH;

        if(!is_writable($TASKS_DIR)) throw new Exception('ERROR - Tasks directory not writable');

        if(empty($_ENV["LOGS_DIR"])) throw new Exception("ERROR - Logs directory configuration empty");




        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });

    $group->delete('/dir', function (Request $request, Response $response, array $args) {

        $data = [];

        $params = array_change_key_case($request->getQueryParams(), CASE_UPPER);

        if(empty($params["DIR_NAME"])) throw new Exception("ERROR - Missing name of the directory being deleted");


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

            if(strpos($params["DIR_NAME"], $TASKS_DIR) === false){
                throw new Exception("ERROR - Directory being deleted out of range");
            }

            $aCONTENT = scandir($params["DIR_NAME"]);
            if(count($aCONTENT) > 2) throw new Exception("ERROR - Directory being deleted not empty");

            if(!is_writable($params["DIR_NAME"])) throw new Exception('ERROR - Directory being deleted not writable');

            if(!rmdir($params["DIR_NAME"])) {
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
