<?php

use DI\Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;

require '../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable("../");
$dotenv->load();


// Registering config parameters
$config_path = "../config/";
$container_config = [];
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


$container = new Container();
$container->set('configs', $container_config);

$container->set('errorHandler', function ($container) {
    return function ($request, $response, $exception) use ($container) {
        $data = [];
        $data["status"] = "Engine error";
        $data["message"] = $exception->getMessage();

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(500)
                        ->withHeader("Content-Type", "application/json");
    };
});


AppFactory::setContainer($container);


//Starting Slim
$app = AppFactory::create();
$base_path = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
$app->setBasePath($base_path);


//Starting Jwt Authenticationim
$app->add(new Tuupola\Middleware\JwtAuthentication([
    "secure" => false,
    "secret" => $_ENV["JWT_SECRET"],

    "ignore" => [$base_path."/auth/login", $base_path."/test"],

    "error" => function ($response, $arguments) {
        $data = [];
        $data["status"] = "Authentication error";
        $data["message"] = $arguments["message"];

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withHeader("Content-Type", "application/json");
    }
]));


// Add Custom Error Handler
$customErrorHandler = function (
    Request $request,
    Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails,
    ?LoggerInterface $logger = null
) use ($app) {

    $data = [];
    $data["status"] = "Engine error";
    $data["message"] = $exception->getMessage();

    $response = $app->getResponseFactory()->createResponse();
    $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return $response->withStatus(500)
                    ->withHeader("Content-Type", "application/json");
};

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setDefaultErrorHandler($customErrorHandler);


foreach (glob("./api/*.php") as $filename) {
    require $filename;
}

$app->run();
