<?php

use Slim\Http\Request;
use Slim\Http\Response;

$auth = new \Middleware\Authentication($app->getContainer());

// Route
$app->get('/', function(Request $request, Response $response) {
  return $response->withStatus(301)->withHeader('Location', '/docs/');
});

$app->get('/users', function(Request $request, Response $response) {
  $query = $this->db->prepare('SELECT `id`, `name`, `role` FROM `users`');
  $query->execute();
  $rows = $query->fetchAll();
  return $response->withJson($rows);
});

$app->get('/user/{id}', function(Request $request, Response $response, $args) {
  $id = $args['id'];
  $query = $this->db->prepare('SELECT `id`, `name`, `role` FROM `users` WHERE id = :id');
  $query->execute(['id' => $id]);
  $row = $query->fetch();
  if (! $row) {
    return $response->withStatus(404)->write('404 Not Found');
  }
  return $response->withJson($row);
})->add($auth);

