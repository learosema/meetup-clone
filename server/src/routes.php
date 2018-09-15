<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes
$app->get('/', function(Request $request, Response $response) {
    return $response->withStatus(301)->withHeader('Location', '/docs/');
});

$app->get('/hello', function(Request $request, Response $response) {
    return $response->withJson(['response' => 'Hello!']);
});