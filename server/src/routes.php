<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Auth Middleware (todo: use tuupola/slim-basic-auth)
$auth = function ($request, $response, $next) {
  $response = $response->withHeader('WWW-Authenticate','Basic realm="protected"');
  $authorization = $request->getHeaderLine('Authorization');
  $authenticated = false;
  if ($authorization) {
    list ($authType, $code) = explode(' ', $authorization);
    if ($authType === "Basic") {
      // TODO: get from DB
      $authenticated = base64_decode($code) === 'lea:lea'; 
    }
  }
  if (! $authenticated) {
    $response = $response->withStatus(401);
    $response->getBody()->write('Access denied.');
    return $response;
  }
  $response = $next($request, $response);
  return $response;
};

// Route
$app->get('/', function(Request $request, Response $response) {
  return $response->withStatus(301)->withHeader('Location', '/docs/');
});

$app->get('/users', function(Request $request, Response $response) {
  return $response->withJson(['response' => 'Hello!']);
});

$app->get('/user/{id}', function(Request $request, Response $response, $args) {
  $id = $args['id'];
  return $response->withJson(['response' => "Hello $id!"]);
});



