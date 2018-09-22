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
  return $response->withJson($this->userService->getUsers());
});

// GET /user/{id}
// Get User By ID
$app->get('/user/{id}', function(Request $request, Response $response, $args) {
  $user = $this->userService->getUserById($args['id']);
  if (! $row) {
    return $response->withStatus(404)->write('404 Not Found');
  }
  return $response->withJson($user);
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
  if ($this->userService->addUser($user)) {
    return $response->withJson(['response' => 'user created.']);
  } else {
    return $response->withStatus(409)->write('User already exists.');
  }
});

// DELETE /user
// Delete account
$app->delete('/user', function(Request $request, Response $response, $args) {
  if ($this->userService->deleteUser($this->identity->id)) {
    return $response->withJson(['response' => 'user deleted.']);
  } else {
    return $response->withStatus(404)->withJson(['response' => 'User not found.']);
  }
})->add($auth);

// GET /groups
// List all groups
$app->get('/groups', function(Request $request, Response $response, $args) {
  try {
    return $response->withJson($this->groupService->getGroups());
  } catch (Exception $ex) {
    return $response->withStatus(500)->write($ex->message);
  }
});

// POST /groups
// Create group
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
    $this->groupService->createGroup($group);
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