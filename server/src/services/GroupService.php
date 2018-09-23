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
    if ($query->rowCount() === 0) {
      // user is not a member of this group
      // or the group does not exist
      return FALSE;
    }
    $row = $query->fetch();
    return $row['role'];
  }

  public function createGroup($group) {
    $query = $this->db->prepare('INSERT INTO groups (id, name, description) VALUES (:id, :name, :description)');
    $query->execute([
      ':id' => $group['id'],
      ':name' => $group['name'],
      ':description' => $group['description']
    ]);
  }

  public function updateGroup($group) {
    try {
      $updateQuery = [];
      $queryParams = [':id' => $group['id']];
      foreach (['name', 'description'] as $k) {
        if ($group[$k]) {
          array_push($updateQuery, "`$k` = :$k");
          $queryParams[":$k"] = $user[$k];
        }
      }
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
    if ($query->rowCount() !== 1) {
      return FALSE;
    }
    $query = $this->db->prepare('DELETE FROM group_members WHERE group_id = :id');
    $query->execute([
      ':id' => $groupId
    ]);
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
    if (isUserInGroup($groupId, $userId)) {
      return FALSE;
    }
    $query = $this->db->prepare('INSERT INTO group_members (group_id, user_id, role) VALUES (:group_id, :user_id, :role)');
    $query->execute([
      ':group_id' => $groupId,
      ':user_id' => $userId,
      ':role' => $role
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

  public function addGroupEvent($groupEvent) {
    // $query = $this->db->prepare('INSERT INTO group_events (id, group_id, name, description, location, address, lat, lon, date) VALUES ()');
    throw new Exception("not implemented yet.");
  }

  public function deleteGroupEvent($groupEvent) {
    throw new Exception("not implemented yet.");
  }

  public function updateGroupEvent($groupEvent) {
    throw new Exception("not implemented yet.");
  }

  public function addGroupEventAttendee() {
    throw new Exception("not implemented yet.");
  }

  public function updateGroupEventAttendee($rsvp) {
    throw new Exception("not implemented yet.");
  }
}