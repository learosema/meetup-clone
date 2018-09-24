<?php

namespace Services;

class UserService {

  public function __construct($db) {
    $this->db = $db;
  }

  public function validate($id, $password) {
    // TODO: encrypt passwords
    $query = $this->db->prepare('SELECT id, name, role FROM users WHERE id = :id AND password = :password AND active = 1');
    $query->execute([':id' => $id, ':password' => $password]);
    $row = $query->fetch();
    if (! $row) {
      return FALSE;
    }
    return (object) $row;
  }

  public function getUsers() {
    $query = $this->db->prepare('SELECT id, name, role FROM users WHERE active = 1');
    $query->execute();
    $rows = $query->fetchAll();
    return $rows;
  }

  public function getUserById($id) {
    $query = $this->db->prepare('SELECT id, name, role FROM users WHERE id = :id AND active = 1');
    $query->execute([':id' => $id]);
    $row = $query->fetch();
    if (! $row) {
      return NULL;
    }
    return $row;
  }

  public function addUser($user) {
    try {
      $query = $this->db->prepare('INSERT INTO users (id, name, password, email, role) VALUES (:id, :name, :password, :email, :role)');
      $query->execute([
        ':id' => $user['id'],
        ':name' => $user['name'],
        ':password' => $user['password'],
        ':email' => $user['email'],
        ':role' => array_key_exists('role', $user) ? $user['role'] : 'user'
      ]);
      return true;
    } catch (PDOException $ex) {
      return false;
    }
  }

  public function activateUser($userId) {
    $query = $this->db->prepare('UPDATE users SET active = 1 WHERE id = :id');
    $query->execute([':id' => $userId]);
    return ($query->rowCount() === 1);
  }

  public function updateUser($user) {
    try {
      $updateQuery = [];
      $queryParams = [':id' => $user['id']];
      foreach (['name', 'password', 'email', 'role'] as $k) {
        if (array_key_exists($k, $user)) {
          array_push($updateQuery, "`$k` = :$k");
          $queryParams[":$k"] = $user[$k];
        }
      }
      if (count($queryParams) > 0) {
        $query = $this->db->prepare('UPDATE users SET '. implode(", ", $updateQuery).' WHERE id = :id');
        $query->execute($queryParams);
      }
      return ($query->rowCount() == 1);
    } catch (PDOException $ex) {
      return false;
    }
  }

  public function deleteUser($userId) {
    try {
      $query = $this->db->prepare('DELETE FROM users WHERE `id` = :id');
      $query->execute([':id' => $userId]);
      return ($query->rowCount() == 1);
    } catch (PDOException $ex) {
      return false;
    }
  }

}