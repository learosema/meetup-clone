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
$app->delete('/user', function(Request $request, Response $response, $args) {
  try {
    $query = $this->db->prepare('DELETE FROM users WHERE `id` = :id');
    $query->execute([':id' => $this->identity->id]);
  } catch (PDOException $ex) {
    return $response->withStatus(404)->write('Not found');
  }
})->add($auth);

$app->get('/groups', function(Request $request, Response $response, $args) {
  try {
    $query = $this->db->prepare('SELECT `id`, `name`, `description` FROM `groups`');
  } catch (PDOException $ex) {
    return $response->withStatus(500)->write($ex->message);
  }
});

$app->post('/group', function(Request $request, Response $response, $args) {
  try {
    $query = $this->db->prepare('INSERT INTO `groups` (`id`, `name`, `description`) VALUES (:id, :name, :description)');
    $group = $request->getParsedBody();
    // TODO: validation. add creator of group as member and stuff.
    if (!isset($group['id']) ||
      !isset($group['name']) ||
      !isset($group['description'])) {      
      return $response->withStatus(400)->write('Bad Request');
    }
    $query->execute([
      ':id' => $group['id'],
      ':name' => $group['name'],
      ':description' => $group['description']
    ]);
    $addMemberQuery = $this->db->prepare('INSERT INTO `group_members` (`group_id`, `user_id`, `role`) VALUES (:group_id, :user_id, :role)');
    $addMemberQuery->execute([
      ':group_id' => $group['id'],
      ':user_id' => $this->identity->id,
      'role' => 'admin'
    ]);
  } catch (PDOEXception $ex) {
    return $response->withStatus(500)->write($ex->message);
  }
})->add($auth);

$app->put('/group', function (Request $request, Response $response, $args) {
  return $response->withStatus(500)->write('not implemented yet.');
});

$app->delete('/group/{id}', function (Request $request, Response $response, $args) {
  return $response->withStatus(500)->write('not implemented yet.');
});