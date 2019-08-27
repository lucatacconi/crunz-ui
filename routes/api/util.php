<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->group('/util', function () use ($app) {

    $app->get('/menu', function ($request, $response, $args) {

        $params = array_change_key_case($request->getParams(), CASE_UPPER);

        $aMENUs = $this->get('app_configs')["menu"];

        return $response->withStatus(200)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($aMENUs, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    });

    $app->get('/routes', function ($request, $response, $args) {

        $params = array_change_key_case($request->getParams(), CASE_UPPER);

        $aMENUs = $this->get('app_configs')["routes"];

        return $response->withStatus(200)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($aMENUs, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    });

});
