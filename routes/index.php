<?php

use DI\Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

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


//Starting Jwt Authentication middleware
$jwtAuthMiddleware = function (Request $request, RequestHandlerInterface $handler) use ($base_path): Response {
    $uriPath = $request->getUri()->getPath();

    // Define paths to ignore for JWT authentication
    $ignoredPaths = [
        $base_path . "/auth/login",
        $base_path . "/tools"
    ];

    // If the current path is among the ignored ones, skip the check
    foreach ($ignoredPaths as $ignoredPath) {
        if ($uriPath === $ignoredPath || str_starts_with($uriPath, $ignoredPath . '/')) {
            return $handler->handle($request);
        }
    }

    $authHeader = $request->getHeaderLine('Authorization');
    $token = null;

    // Extract the Bearer token from the Authorization header
    if (!empty($authHeader) && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
    }

    if (!$token) {
        $response = new \Slim\Psr7\Response();
        $data = [
            "status" => "Authentication error",
            "message" => "Token not found"
        ];
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withHeader("Content-Type", "application/json")->withStatus(401);
    }

    try {
        // Validate the token using firebase/php-jwt ^7.0 with the Key object
        $secret = $_ENV["JWT_SECRET"];
        $decoded = JWT::decode($token, new Key($secret, 'HS256'));

        // Save the decoded data in the request to use it in the controllers
        $request = $request->withAttribute('jwt', $decoded);

    } catch (\Exception $e) {
        $response = new \Slim\Psr7\Response();
        $data = [
            "status" => "Authentication error",
            "message" => $e->getMessage()
        ];
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withHeader("Content-Type", "application/json")->withStatus(401);
    }

    return $handler->handle($request);
};
// Aggiungi il middleware alla tua applicazione Slim
$app->add($jwtAuthMiddleware);

// Starting HTTP Basic Authentication
$basicAuthMiddleware = function (Request $request, RequestHandlerInterface $handler) use ($base_path): Response {
    $path = $base_path . "/tools";
    $uriPath = $request->getUri()->getPath();

    // Check if the request matches the protected path
    if (str_starts_with($uriPath, $path)) {
        $authHeader = $request->getHeaderLine('Authorization');

        $authenticated = false;
        if (str_starts_with($authHeader, 'Basic ')) {
            $decoded = base64_decode(substr($authHeader, 6));
            if ($decoded !== false && str_contains($decoded, ':')) {
                [$user, $password] = explode(':', $decoded, 2);

                // Check credentials from $_ENV
                // Use hash_equals to prevent timing attacks
                if (isset($_ENV["TOOLS_LOGIN"], $_ENV["TOOLS_PASSWORD"]) &&
                    hash_equals($_ENV["TOOLS_LOGIN"], $user) &&
                    hash_equals($_ENV["TOOLS_PASSWORD"], $password)) {
                    $authenticated = true;
                }
            }
        }

        if (!$authenticated) {
            $response = new Response();
            $data = [
                "status" => "Authentication error",
                "message" => "Authentication required"
            ];

            $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            return $response
                ->withHeader("Content-Type", "application/json")
                ->withHeader("WWW-Authenticate", 'Basic realm="Protected"')
                ->withStatus(401);
        }
    }

    return $handler->handle($request);
};
$app->add($basicAuthMiddleware);


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


$route_path = "./api";

$directoryIterator = new \RecursiveDirectoryIterator($route_path);
$recursiveIterator = new \RecursiveIteratorIterator($directoryIterator);


$quotedSuffix = \preg_quote('.php', '/');
$regexIterator = new \RegexIterator( $recursiveIterator, "/^.+{$quotedSuffix}$/i", \RecursiveRegexIterator::GET_MATCH );

$files = \array_map(
    static function (array $file) {
        return new \SplFileInfo(\reset($file));
    },
    \iterator_to_array($regexIterator)
);

foreach ($files as $route_file) {
    require_once $route_file;
}

$app->addBodyParsingMiddleware();
$app->run();
