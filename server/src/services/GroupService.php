<?php

namespace Services;

class GroupService {

  public function __construct($db) {
    $this->db = $db;
  }

  public function getGroups() {
    try {
      $query = $this->db->prepare('SELECT id, name, description FROM groups');
      $query->execute();
      return $query->fetchAll();
    } catch (PDOException $ex) {
      return FALSE;
    }
  }

  public function getGroupById($groupId) {
    try {
      $query = $this->db->prepare('SELECT id, name, description FROM groups WHERE id = :id');
      $query->execute([':id' => $groupId]);
      return $query->fetch();
    } catch (PDOException $ex) {
      return FALSE;
    }
  }

  public function getGroupMembers($groupId) {
    $query = $this->db->prepare('SELECT users.id, users.name, users.email FROM users LEFT JOIN group_members ON users.id = group_members.user_id WHERE group_members.group_id = :group_id and users.active = 1');
    $query->execute([
      ':group_id' => $groupId
    ]);
    return $query->fetchAll();
  }

  public function getGroupMemberRole($groupId, $userId) {
    $query = $this->db->prepare('SELECT role FROM group_members WHERE group_id = :group_id AND user_id = :user_id');
    $query->execute([
      ':group_id' => $groupId,
      ':user_id' => $userId
    ]);
    $rows = $query->fetchAll();
    if (count($rows) !== 1) {
      return FALSE;
    }
    return $rows[0]['role'];
  }

  public function createGroup($group) {
    $query = $this->db->prepare('INSERT INTO groups (id, name, description, timestamp) VALUES (:id, :name, :description, :timestamp)');
    $query->execute([
      ':id' => $group['id'],
      ':name' => $group['name'],
      ':description' => $group['description'],
      ':timestamp' => date('c')
    ]);
  }

  public function updateGroup($group) {
    try {
      $updateQuery = [];
      $queryParams = [':id' => $group['id']];
      foreach (['name', 'description'] as $k) {
        if (array_key_exists($k, $group) && $group[$k]) {
          array_push($updateQuery, "`$k` = :$k");
          $queryParams[":$k"] = $group[$k];
        }
      }
      array_push($updateQuery, '`timestamp` = :timestamp');
      $queryParams[':timestamp'] = date('c');
      if (count($queryParams) > 0) {
        $query = $this->db->prepare('UPDATE groups SET '. implode(", ", $updateQuery).' WHERE `id` = :id');
        $query->execute($queryParams);
      }
      return ($query->rowCount() == 1);
    } catch (PDOException $ex) {
      return false;
    }
  }

  public function deleteGroup($groupId) {
    $query = $this->db->prepare('DELETE FROM groups WHERE id = :id');
    $query->execute([
      ':id' => $groupId
    ]);
    if ($query->rowCount() != 1) {
      return FALSE;
    }
    $query = $this->db->prepare('DELETE FROM group_members WHERE group_id = :id');
    $query->execute([
      ':id' => $groupId
    ]);
    return TRUE;
  }

  public function isUserInGroup($groupId, $userId) {
    $query = $this->db->prepare('SELECT COUNT(*) AS count FROM group_members WHERE group_id = :group_id AND user_id = :user_id');
    $query->execute([
      ':group_id' => $groupId,
      ':user_id' => $userId
    ]);
    $result = $query->fetch();
    return ($result['count'] == 1);
  }

  public function addMember($groupId, $userId, $role = 'member') {
    if ($this->isUserInGroup($groupId, $userId)) {
      return FALSE;
    }
    $query = $this->db->prepare('INSERT INTO group_members (group_id, user_id, role, timestamp) VALUES (:group_id, :user_id, :role, :timestamp)');
    $query->execute([
      ':group_id' => $groupId,
      ':user_id' => $userId,
      ':role' => $role,
      ':timestamp' => date('c')
    ]);
    return ($query->rowCount() === 1);
  }

  public function deleteMember($groupId, $userId) {
    $query = $this->db->prepare('DELETE FROM group_members WHERE group_id = :group_id AND user_id = :user_id');
    $query->execute([
      ':group_id' => $groupId,
      ':user_id' => $userId
    ]);
    return ($query->rowCount() === 1);
  }

}