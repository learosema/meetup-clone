<?php

use Slim\Http\Request;
use Slim\Http\Response;

$auth = new \Middleware\Authentication($app->getContainer());


// OPTIONS Preflight Request for CORS
$app->options('/{routes:.+}', function ($request, $response, $args) {
  return $response;
});

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
  if (! $user) {
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
    return $response->withStatus(400)->withJson(['response' => 'Bad Request.']);
  }
  unset($user['role']);
  if ($this->userService->addUser($user)) {
    return $response->withJson(['response' => 'user created.']);
  } else {
    return $response->withStatus(409)->withJson(['response' => 'User already exists.']);
  }
});

// PUT /user
// Update user information
$app->put('/user', function(Request $request, Response $response, $args) {
  $user = $request->getParsedBody();
  $userId = $user['id'];
  if ($this->identity->role !== "admin") {
    if ($userId != $this->identity->id) {
      return $response->withStatus(403)->withJson(['response' => 'Permission denied.']);
    }
    unset($user['role']);
  }
  $this->userService->updateUser($user);
})->add($auth);

// DELETE /user/{id}
// Delete account
$app->delete('/user/{id}', function(Request $request, Response $response, $args) {
  $userId = $args['id'];
  if ($this->identity->role !== "admin") {
    if ($userId !== $this->identity->id) {
      return $response->withStatus(403)->withJson(['response' => 'Permission denied.']);
    }
  }
  if ($this->userService->deleteUser($userId)) {
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
    $group = $request->getParsedBody();
    // TODO: validation. add creator of group as member and stuff.
    if (!isset($group['id']) ||
      !isset($group['name']) ||
      !isset($group['description'])) {      
      return $response->withStatus(400)->withJson(['response' => 'Bad Request.']);
    }
    $this->groupService->createGroup($group);
    $this->groupService->addMember($group['id'], $this->identity->id, 'admin');
  } catch (PDOEXception $ex) {
    return $response->withStatus(500)->write($ex->message);
  }
})->add($auth);

// PUT /group/{id} 
// Update group
$app->put('/group/{id}', function (Request $request, Response $response, $args) {
  $groupId = $args['id'];
  $userRole = $this->groupService->getGroupMemberRole($groupId, $this->identity->id);
  if ($userRole === FALSE) {
    return $response->withStatus(403)->withJson(['response' => 'Not in group or group does not exist.']);
  }
  if ($userRole !== "admin") {
    return $response->withStatus(403)->withJson(['response' => 'Insufficient permissions to delete the group.']);
  }
  $group = $request->getParsedBody();
  $group['id'] = $groupId;
  if ($this->updateGroup($group)) {
    return $response->withJson(['response' => 'Group updated.']);
  } else {
    return $response->withStatus(404)->withJson(['response' => 'Group not found.']);
  }
})->add($auth);

// DELETE /group/{id}
// Delete group
$app->delete('/group/{id}', function (Request $request, Response $response, $args) {
  $groupId = $args['id'];
  if ($this->identity->role === "admin") {
    $userRole = "admin";
  } else {
    $userRole = $this->groupService->getGroupMemberRole($groupId, $this->identity->id);
  }
  if ($userRole === FALSE) {
    return $response->withStatus(403)->withJson(['response' => 'Not in group or group does not exist.']);
  }
  if ($userRole !== "admin") {
    return $response->withStatus(403)->withJson(['response' => 'Insufficient permissions to delete the group.']);
  }
  if (! $this->groupService->deleteGroup($groupId)) {
    return $response->withStatus(404)->withJson(['response' => 'Group not found.']);
  }
  return $response->withJson(['response' => 'User deleted.']);
})->add($auth);

// GET /group/{id}/members
// Get group members
$app->get('/group/{id}/members', function (Request $request, Response $response, $args) {
  $groupId = $args['id'];
  $group = $this->groupService->getGroupById($groupId);
  if (! $group) {
    return $response->withStatus(404)->withJson(['response' => 'Group not found.']);
  }
  return $response->withJson($this->groupService->getGroupMembers($groupId));
});

// POST /group/{id}/members
// Add currently logged in user to group
$app->post('/group/{id}/members', function (Request $request, Response $response, $args) {
  $groupId = $args['id'];
  $group = $this->groupService->getGroupById($groupId);
  if (! $group) {
    return $response->withStatus(404)->withJson(['response' => 'Group not found.']);
  }
  $userId = $this->identity->id;
  if ($this->groupService->addMember($groupId, $userId)) {
    return $response->withJson(['response' => "User $userId added to group $groupId."]);
  } else {
    return $response->withStatus(409)->withJson(['response' => "User $userId already in group $groupId."]);
  }
})->add($auth);

$app->delete('/group/{id}/members', function (Request $request, Response $response, $args) {
  $groupId = $args['id'];
  $group = $this->groupService->getGroupById($groupId);
  if (! $group) {
    return $response->withStatus(404)->withJson(['response' => 'Group not found.']);
  }
  $userId = $this->identity->id;
  if ($this->groupService->deleteMember($groupId, $userId)) {
    return $response->withJson(['response' => "User $userId deleted from group $groupId."]);
  } else {
    return $response->withStatus(404)->withJson(['response' => "User $userId not in group $groupId."]);
  }
})->add($auth);

// Catch-all route to serve a 404 Not Found page if none of the routes match
// NOTE: make sure this route is defined last
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function($req, $res) {
  $handler = $this->notFoundHandler; // handle using the default Slim page not found handler
  return $handler($req, $res);
});