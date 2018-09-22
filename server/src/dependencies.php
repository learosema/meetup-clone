<?php
// DIC configuration

$container = $app->getContainer();

$container['db'] = function ($c) {
  $db_config = $c->get('settings')['db'];
  $db = new PDO($db_config['connection'], $db_config['user'], $db_config['password']);
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  return $db;
};

$container['userService'] = function ($c) {
  return new \Services\UserService($c->db);
};

$container['groupService'] = function ($c) {
  return new \Services\GroupService($c->db);
};