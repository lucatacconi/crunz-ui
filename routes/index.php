<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';

$dotenv = new Dotenv\Dotenv("../");
$dotenv->load();

date_default_timezone_set(getenv("TIMEZONE"));


$container_config = array();

// Registering config parameters
$config_path = "../config/";
foreach (glob($config_path."*.json") as $filename) {
    $config_content = file_get_contents($filename);

    if(!empty($config_content)){
        $container_config["app_configs"][ str_replace(array($config_path, ".json"), "", $filename) ] = json_decode($config_content, true);
    }
}

//Base application path calculation
$adir = explode("/", __DIR__);
$dir = implode("/", $adir);

while (!\file_exists($dir.'/'.'composer.json')) {
    array_pop($adir);
    $dir = implode("/", $adir);
}

$container_config["app_configs"]["paths"] = [];
$container_config["app_configs"]["paths"]["base_path"] = $dir;

$container = new \Slim\Container($container_config);

$container['errorHandler'] = function ($container) {
    return function ($request, $response, $exception) use ($container) {
        $data = [];
        $data["status"] = "Engine error";
        $data["message"] = $exception->getMessage();

        return $response->withStatus(500)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    };
};


//Starting Slim
$app = new \Slim\App($container);

//Security layer
$app->add(new Tuupola\Middleware\JwtAuthentication([
    "secret" => getenv("JWT_SECRET"),

    "ignore" => ["/auth/login", "/test"],

    "error" => function ($response, $arguments) {
        $data = [];
        $data["status"] = "Authentication error";
        $data["message"] = $arguments["message"];

        return $response
            ->withHeader("Content-Type", "application/json")
            ->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
]));

foreach (glob("./api/*.php") as $filename) {
    require $filename;
}

$app->run();
