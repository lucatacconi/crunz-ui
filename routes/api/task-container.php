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
        sort($data);

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });

    $group->get('/tree/display', function (Request $request, Response $response, array $args) {

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

        $aTASKS_DIR = CrunzUITools::taskDirectoryRotator($TASKS_DIR);
        sort($aTASKS_DIR);

        $data = [];
        $data = [ 'subdir' => '/', 'description' => 'Main task', 'children' => [] ];


        foreach($aTASKS_DIR as $aTASKS_DIR_cnt => $tasks_dir){

            if(empty($tasks_dir)){
                continue;
            }

            $aPATH_dir = explode("/", $tasks_dir);

            if(empty($aPATH_dir[1])){
                continue;
            }

            $depth = count($aPATH_dir) - 1;

            $section_ref = &$data["children"];
            $path = '';
            $descr = '';

            foreach($aPATH_dir as $path_cnt => $path_data){

                if($path_cnt == 0){
                    continue;
                }

                $child_founded =false;
                $child_key_ref = -1;

                $path .= '/'. $path_data;
                $descr .= ucfirst($path_data) . ' - ';

                if(!empty($section_ref)){
                    foreach($section_ref as $child_key => $child_data){
                        if($child_data["subdir"] == $path){
                            $child_founded = true;
                            $child_key_ref = $child_key;
                            break;
                        }
                    }
                }

                if($child_founded){
                    $section_ref =  &$section_ref[$child_key]["children"];
                }else{
                    $row = [];
                    $row["subdir"] = $path;
                    $row["description"] = rtrim($descr, ' - ');

                    if($path_cnt < $depth){
                        $row["children"] = [];
                    }


                    $section_ref[] = $row;
                }
            }
        }

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });

    $group->post('/dir', function (Request $request, Response $response, array $args) {

        $data = [];

        $params = array_change_key_case($request->getQueryParams(), CASE_UPPER);

        if(!isset($params["PATH"]) || $params["PATH"] == '') throw new Exception("ERROR - Missing name of the directory being added");

        if (preg_match('/[^a-zA-Z0-9\\/_-]/', $params["PATH"])) {
            throw new Exception("ERROR - Directory being added contains not allowed characters (Only a-z, A-Z, 0-9, -, _, / characters allowed)");
        }

        $params["PATH"] = str_replace(['.'], '', $params["PATH"]);
        $params["PATH"] = str_replace(['//'], '/', $params["PATH"]);

        if($params["PATH"] == "/") throw new Exception("ERROR - Directory being added can not be main path");

        $params["PATH"] = rtrim($params["PATH"], "/");



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

            if(strlen($params["PATH"]) > 1){
                $params["PATH"] = rtrim($params["PATH"],"/");
            }

            $aDESTINATION = explode("/", $params["PATH"]);
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

            if(is_dir( $TASKS_DIR  . $params["PATH"] )){
                throw new Exception("ERROR - Directory being added already present");
            }

            if (!mkdir( $TASKS_DIR  . rtrim($params["PATH"],"/") )) {
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

        if(empty($params["PATH"])) throw new Exception("ERROR - Missing name of the directory being deleted");

        $params["PATH"] = str_replace(['.'], '', $params["PATH"]);

        if($params["PATH"] == "/") throw new Exception("ERROR - Directory being deleted can not be main path");

        if (preg_match('/[^a-zA-Z0-9\\/_-]/', $params["PATH"])) {
            throw new Exception("ERROR - Directory being deleted contains not allowed characters (Only a-z, A-Z, 0-9, -, _, / characters allowed)");
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

        $TASKS_DIR = $crunz_base_dir . "/" . ltrim($crunz_config["source"], "/");

        try {

            $dir_delete = $TASKS_DIR . $params["PATH"];

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
