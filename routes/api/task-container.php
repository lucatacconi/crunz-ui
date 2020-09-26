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

});
