<?php

namespace Services;

class GroupService {

  public function __construct($db) {
    $this->db = $db;
  }

  public function getGroups() {
    try {
      $query = $db->prepare('SELECT * FROM `groups`');
      $query->execute();
      return $query->fetchAll();
    } catch (PDOException $ex) {
      return FALSE;
    }
  }

  public function getGroupById($id) {
    try {
      $query = $db->prepare('SELECT * FROM `groups` WHERE `id` = :id');
      $query->execute([':id' => $id]);
      return $query->fetchAll();
    } catch (PDOException $ex) {
      return FALSE;
    }
  }

  public function createGroup($group) {
    try {
      $query = $this->db->prepare('INSERT INTO `groups` (`id`, `name`, `description`) VALUES (:id, :name, :description)');
      $query->execute([
        ':id' => $group['id'],
        ':name' => $group['name'],
        ':description' => $group['description']
      ]);
    } catch (PDOEXception $ex) {
      return $response->withStatus(500)->write($ex->message);
    }
  }

  public function deleteGroup($groupId) {
    throw new Exception("Not Implemeneted Yet");
  }

  public function addMember($groupId, $userId, $role = 'member') {
    $query = $this->db->prepare('INSERT INTO `group_members` (`group_id`, `user_id`, `role`) VALUES (:group_id, :user_id, :role)');
    $query->execute([
      ':group_id' => $groupId,
      ':user_id' => $userId,
      ':role' => $role
    ]);
    return ($query->rowCount() == 1);
  }

  public function deleteMember() {
    throw new Exception("Not Implemeneted Yet");
  }
}