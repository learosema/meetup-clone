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
  return new \Services\UserService($c->db, $c->env);
};

$container['groupService'] = function ($c) {
  return new \Services\GroupService($c->db);
};

$container['eventService'] = function ($c) {
  return new \Services\EventService($c->db);
};

$container['env'] = function ($c) {
  $dotEnvFile = __DIR__  . '/../db/.env';
  if (! file_exists($dotEnvFile)) {
    @file_put_contents($dotEnvFile, 'salt=' . hash('sha256', date('c').rand()), LOCK_EX);
  }
  $data = [];
  foreach (file($dotEnvFile) as $line) {
    list ($key, $value) = explode('=', $line);
    $data[$key] = $value;
  }
  return (object)$data;
};

