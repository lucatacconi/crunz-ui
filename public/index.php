<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';

$dotenv = new Dotenv\Dotenv("../");
$dotenv->load();

date_default_timezone_set(getenv("TIMEZONE"));

$twig_config = [];
$twig_config['cache'] = false;

$loader = new \Twig\Loader\FilesystemLoader('../assets/templates');
$twig = new \Twig\Environment($loader, $twig_config);


// Registering config parameters
$config_path = "../config/";
foreach (glob($config_path."*.json") as $filename) {
    $config_content = file_get_contents($filename);

    if(!empty($config_content)){
        $container_config[ str_replace(array($config_path, ".json"), "", $filename) ] = json_decode($config_content, true);
    }
}




$tpl_data = [];
$tpl_data["run_mode"] = getenv("RUN_MODE");
$tpl_data["application"] = $container_config["application"];

echo $twig->render('index.html', $tpl_data);
