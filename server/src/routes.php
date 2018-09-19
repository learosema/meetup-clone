<?php

use Slim\Http\Request;
use Slim\Http\Response;

$auth = new \Middleware\Authentication($app->getContainer());

// GET /
// redirects to swagger OpenAPI documentation
$app->get('/', function(Request $request, Response $response) {
  return $response->withStatus(301)->withHeader('Location', '/docs/');
});

// GET /auth 
// Test route for authenticated requests, returns identity object
$app->get('/auth', function(Request $request, Response $response) {
  return $response->withJson(['identity' => $this->identity]);
})->add($auth);

// GET /users
// Get all users
$app->get('/users', function(Request $request, Response $response) {
  $query = $this->db->prepare('SELECT `id`, `name`, `role` FROM `users`');
  $query->execute();
  $rows = $query->fetchAll();
  return $response->withJson($rows);
});

// GET /user/{id}
// Get User By ID
$app->get('/user/{id}', function(Request $request, Response $response, $args) {
  $id = $args['id'];
  $query = $this->db->prepare('SELECT `id`, `name`, `role` FROM `users` WHERE id = :id');
  $query->execute([':id' => $id]);
  $row = $query->fetch();
  if (! $row) {
    return $response->withStatus(404)->write('404 Not Found');
  }
  return $response->withJson($row);
})->add($auth);

// POST /user
// Register a new user
$app->post('/user', function(Request $request, Response $response, $args) {
  $user = $request->getParsedBody();
  // TODO: better validation
  // https://github.com/DavidePastore/Slim-Validation#json-requests
  if (!isset($user['id']) ||
      !isset($user['password']) ||
      !isset($user['email']) ||
      !isset($user['name'])) {
    return $response->withStatus(400)->write('Bad Request');
  }
  try {
    $query = $this->db->prepare('INSERT INTO users (`id`, `name`, `password`, `email`) VALUES (:id, :name, :password, :email)');
    $query->execute([
      ':id' => $user['id'],
      ':name' => $user['name'],
      ':password' => $user['password'],
      ':email' => $user['email']
    ]);
    return $response->withJson([
      'id' => $user['id'],
      'name' => $user['name'],
      'email' => $user['email']
    ]);
  } catch (PDOException $ex) {
    return $response->withStatus(409)->write('User already exists');
  }
});

// DELETE /user
// Delete account
$app->delete('/user', function() {
  try {
    $query = $this->db->prepare('DELETE FROM users WHERE `id` = :id');
    $query->execute([':id' => $this->identity->id]);
  } catch (PDOException $ex) {
    return $response->withStatus(404)->write('Not found');
  }
})->add($auth);