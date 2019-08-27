<?php

$app->group('/test', function () use ($app) {
    $app->get('/conn', function ($request, $response, $args) {
        $response->getBody()->write("CONN OK");
        return $response;
    });
});
