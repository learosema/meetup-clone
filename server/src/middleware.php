<?php
// Application middleware

// e.g: $app->add(new \Slim\Csrf\Guard);

$app->add(new \Middleware\Cors($app->getContainer()));

$app->options('/[{path:.*}]', function($request, $response, $path = null) {
  return $response;
});