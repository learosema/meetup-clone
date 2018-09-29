<?php

use Slim\Http\Request;
use Slim\Http\Response;

$auth = new \Middleware\Authentication($app->getContainer());


// OPTIONS Preflight Request for CORS
$app->options('/{routes:.+}', function ($request, $response, $args) {
  return $response;
});

$app->get('/env', function (Request $request, Response $response, $args) {
  if ($request->getUri()->getHost() !== 'localhost') {
    return $response->withStatus(403)->withJson(['response' => 'Permission denied.']);
  }
  return $response->withJson($this->env);
});


// GET /
// redirects to swagger OpenAPI documentation
$app->get('/', function(Request $request, Response $response) {
  return $response->withStatus(301)->withHeader('Location', '/docs/');
});

// GET /auth 
// Test route for authenticated requests, returns identity object
$app->get('/auth', function(Request $request, Response $response) {
  return $response->withJson($this->identity);
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

// POST /user/{id}
// Register a new user
$app->post('/user/{id}', function(Request $request, Response $response, $args) {
  $user = $request->getParsedBody();
  $user['id'] = $args['id'];
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

// PUT /user/{id}
// Update user information
$app->put('/user/{id}', function(Request $request, Response $response, $args) {
  $user = $request->getParsedBody();
  $userId = $args['id'];
  $user['id'] = $userId;
  if ($this->identity->role !== "admin") {
    if ($userId != $this->identity->id) {
      // normal users can only update their own user information
      return $response->withStatus(403)->withJson(['response' => 'Permission denied.']);
    }
    foreach (['role', 'active'] as $key) {
      if (array_key_exists($key, $user)) {
        unset($user[$key]);
      }
    }
  }
  $this->userService->updateUser($user);
  return $response->withJson(['response' => 'User updated.']);
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

// POST /group/{id}
// Create group
$app->post('/group/{id}', function(Request $request, Response $response, $args) {
  try {
    $group = $request->getParsedBody();
    $group['id'] = $args['id'];
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

// POST /group/{id}/member/{mid}
// Add user to group
$app->post('/group/{id}/members/{mid}', function (Request $request, Response $response, $args) {
  $groupId = $args['id'];
  $userId = array_key_exists('mid', $args) ? $args['mid'] : $this->identity->id;
  $group = $this->groupService->getGroupById($groupId);
  if (! $group) {
    return $response->withStatus(404)->withJson(['response' => 'Group not found.']);
  }
  if ($this->identity->role !== "admin" && $userId !== $this->identity->id) {
    return $response->withStatus(403)->withJson(['response' => 'Permission denied.']);
  } else {
    $user = $this->userService->getUserById($userId);
    if (! $user) {
      return $response->withStatus(404)->withJson(['response' => 'User not found.']);
    }
  }
  if ($this->groupService->addMember($groupId, $userId)) {
    return $response->withJson(['response' => "User $userId added to group $groupId."]);
  } else {
    return $response->withStatus(409)->withJson(['response' => "User $userId already in group $groupId."]);
  }
})->add($auth);


// DELETE /group/{id}/member/{mid}
// Delete user from group
$app->delete('/group/{id}/members/{mid}', function (Request $request, Response $response, $args) {
  $groupId = $args['id'];
  $userId = array_key_exists('mid', $args) ? $args['mid'] : $this->identity->id;
  $group = $this->groupService->getGroupById($groupId);
  if (! $group) {
    return $response->withStatus(404)->withJson(['response' => 'Group not found.']);
  }
  if ($this->identity->role !== "admin" && $userId !== $this->identity->id) {
    return $response->withStatus(403)->withJson(['response' => 'Permission denied.']);
  } else {
    $user = $this->userService->getUserById($userId);
    if (! $user) {
      return $response->withStatus(404)->withJson(['response' => 'User not found.']);
    }
  }
  if ($this->groupService->deleteMember($groupId, $userId)) {
    return $response->withJson(['response' => "User $userId deleted from group $groupId."]);
  } else {
    return $response->withStatus(404)->withJson(['response' => "User $userId not in group $groupId."]);
  }
})->add($auth);

// GET /group/{id}/events
// Get list of group events
$app->get('/group/{id}/events', function (Request $request, Response $response, $args) {
  $groupId = $args['id'];
  $group = $this->groupService->getGroupById($groupId);
  if (! $group) {
    return $response->withStatus(404)->withJson(['response' => 'Group not found.']);
  }
  $events = $this->eventService->getGroupEvents($groupId);
  $group['events'] = $events;
  return $response->withJson($group);
});

// POST /group/{id}/event/{eid}
// Create new event
$app->post('/group/{id}/event/{eid}', function (Request $request, Response $response, $args) {
  $groupId = $args['id'];
  $eventId = $args['eid'];
  if ($this->identity->role === "admin") {
    $userRole = "admin";
  } else {
    $userRole = $this->groupService->getGroupMemberRole($groupId, $this->identity->id);
  }
  if ($userRole !== "admin") {
    return $response->withStatus(403)->withJson(['response' => 'Permission denied.']);
  }
  $group = $this->groupService->getGroupById($groupId);
  if (! $group) {
    return $response->withStatus(404)->withJson(['response' => 'Group not found.']);
  }
  $event = $this->eventService->getGroupEvent($groupId, $eventId);
  if ($event) {
    return $response->withStatus(409)->withJson(['response' => 'Event already exists. Use PUT to update.']);
  }
  $event = $request->getParsedBody();
  $event['id'] = $eventId;
  $event['group_id'] = $groupId;
  $this->eventService->createGroupEvent($event);
  return $response->withJson(['response' => 'Group created.']);
})->add($auth);

$app->put('/group/{id}/event/{eid}', function (Request $request, Response $response, $args) {
  $groupId = $args['id'];
  $eventId = $args['eid'];
  if ($this->identity->role === "admin") {
    $userRole = "admin";
  } else {
    $userRole = $this->groupService->getGroupMemberRole($groupId, $this->identity->id);
  }
  if ($userRole !== "admin") {
    return $response->withStatus(403)->withJson(['response' => 'Permission denied.']);
  }
  $group = $this->groupService->getGroupById($groupId);
  if (! $group) {
    return $response->withStatus(404)->withJson(['response' => 'Group not found.']);
  }
  $event = $this->eventService->getGroupEvent($groupId, $eventId);
  if (! $event) {
    return $response->withStatus(404)->withJson(['response' => 'Event not found.']);
  }
  $event = $request->getParsedBody();
  $event['id'] = $eventId;
  $event['group_id'] = $groupId;
  $this->eventService->createGroupEvent($event);
  return $response->withJson(['response' => 'Group created.']);
})->add($auth);

$app->delete('/group/{id}/event/{eid}', function (Request $request, Response $response, $args) {
  $groupId = $args['id'];
  $eventId = $args['eid'];
  if ($this->identity->role === "admin") {
    $userRole = "admin";
  } else {
    $userRole = $this->groupService->getGroupMemberRole($groupId, $this->identity->id);
  }
  if ($userRole !== "admin") {
    return $response->withStatus(403)->withJson(['response' => 'Permission denied.']);
  }
  $group = $this->groupService->getGroupById($groupId);
  if (! $group) {
    return $response->withStatus(404)->withJson(['response' => 'Group not found.']);
  }
  $event = $this->eventService->getGroupEvent($groupId, $eventId);
  if (! $event) {
    return $response->withStatus(404)->withJson(['response' => 'Event not found.']);
  }
  $this->eventService->deleteGroupEvent($groupId, $eventId);
  return $response->withJson(['response' => 'Event deleted.']);
})->add($auth);

$app->post('/group/{id}/event/{eid}/rsvp', function (Request $request, Response $response, $args) {
  $groupId = $args['id'];
  $eventId = $args['eid'];
  $data = $request->getParsedBody();
  if (! array_key_exists('rsvp', $data) || $data['rsvp'] !== 'yes' || $data['rsvp'] !== 'no') {
    return $response->withStatus(422)->withJson(['response' => 'Parameter rsvp missing.']);
  }
  if ($this->identity->role === "admin") {
    $userRole = "admin";
  } else {
    $userRole = $this->groupService->getGroupMemberRole($groupId, $this->identity->id);
  }
  if ($userRole !== "admin") {
    return $response->withStatus(403)->withJson(['response' => 'Permission denied.']);
  }
  $group = $this->groupService->getGroupById($groupId);
  if (! $group) {
    return $response->withStatus(404)->withJson(['response' => 'Group not found.']);
  }
  $event = $this->eventService->getGroupEvent($groupId, $eventId);
  if (! $event) {
    return $response->withStatus(404)->withJson(['response' => 'Event not found.']);
  }
  $this->eventService->submitRSVP($groupId, $eventId, $this->identity->id, $data['rsvp']);
  return $response->withJson(['response' => 'RSVP submitted']);
})->add($auth);

// Catch-all route to serve a 404 Not Found page if none of the routes match
// NOTE: make sure this route is defined last
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function($req, $res) {
  $handler = $this->notFoundHandler; // handle using the default Slim page not found handler
  return $handler($req, $res);
});