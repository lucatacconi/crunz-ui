<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;
use Tuupola\Base62;

$app->group('/task', function () use ($app) {

    $app->get('/', function ($request, $response, $args) {

        $data = [];

        if(empty(getenv("TASK_DIR"))) throw new Exception("ERROR - Tasks directory configuration empty");
        if(empty(getenv("TASK_SUFFIX"))) throw new Exception("ERROR - Wrong tasks configuration");

        $pathtotasks = '..'.getenv("TASK_DIR");

        $directoryIterator = new \RecursiveDirectoryIterator($pathtotasks);
        $recursiveIterator = new \RecursiveIteratorIterator($directoryIterator);


        $quotedSuffix = \preg_quote(getenv("TASK_SUFFIX"), '/');
        $regexIterator = new \RegexIterator( $recursiveIterator, "/^.+{$quotedSuffix}$/i", \RecursiveRegexIterator::GET_MATCH );

        $files = \array_map(
            static function (array $file) {
                return new \SplFileInfo(\reset($file));
            },
            \iterator_to_array($regexIterator)
        );

        foreach ($files as $taskFile) {

            print_r($taskFile->pathName);
            die();


            // $schedule = require

        }








        print_r($files);
        die();



        $data = $files;


        // $data["navMap"] = $navigation_map;
        // $data["bootstrapPage"] = $bootstrapPage;
        // $data["routes"] = $routes;

        return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    });

});
