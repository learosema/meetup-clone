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
    if ($this->isUserInGroup($groupId, $userId)) {
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

  public function getGroupEvents($groupId) {
    $query = $this->db->prepare('SELECT * FROM group_events WHERE group_id = :group_id');
    $query->execute([':group_id' => $groupId]);
    return ($query->fetchAll());
  }

  public function getGroupEvent($groupId, $eventId) {
    $query = $this->db->prepare('SELECT * FROM group_events WHERE group_id = :group_id AND event_id = :event_id');
    $query->execute([
      ':group_id' => $groupId,
      ':event_id' => $eventId
    ]);
    return ($query->fetch());
  }


  public function addGroupEvent($event) {
    $cols = ['id', 'group_id', 'name', 'description', 'location', 'address', 'lat', 'lon', 'date', 'timestamp'];
    $vals = array_map(function($col) { return ':' . $col; }, $cols);
    $strCols = implode(', ', $cols);
    $strVals = implode(', ', $vals);
    $query = $this->db->prepare("INSERT INTO group_events ($cols) VALUES ($vals)");
    $query->execute([
      ':id' => $event['id'], 
      ':group_id' => $event['group_id'], 
      ':name' => $event['name'], 
      ':description' => $event['description'], 
      ':location' => $event['location'], 
      ':address' => $event['address'], 
      ':lat' => $event['lat'], 
      ':lon' => $event['lon'], 
      ':date' => $event['date'], 
      ':timestamp' => date('c')]);
    return ($query->rowCount() === 1);
  }

  public function deleteGroupEvent($eventId) {
    $query = $this->db->prepare('DELETE FROM group_events WHERE id = :id');
    $query->execute([
      ':id' => $eventId
    ]);
    return ($query->rowCount() === 1);
  }

  public function updateGroupEvent($event) {
    $event['timestamp'] = date('c');
    $cols = array_filter(['group_id', 'name', 'description', 'location', 'address', 'lat', 'lon', 'date', 'timestamp'], function($key) {
      return array_key_exists($key, $event);
    });
    $updates = array_map(function($col) { return $col . ' = :' . $col; }, $cols);
    $strUpdates = implode(', ', $cols);
    $query = $this->db->prepare("UPDATE group_events SET $strUpdates WHERE id = :id");
    $query->execute([
      ':id' => $event['id'], 
      ':group_id' => $event['group_id'], 
      ':name' => $event['name'], 
      ':description' => $event['description'], 
      ':location' => $event['location'], 
      ':address' => $event['address'], 
      ':lat' => $event['lat'], 
      ':lon' => $event['lon'], 
      ':date' => $event['date'], 
      ':timestamp' => date('c')]);
    return ($query->rowCount() === 1);
  }

  public function getEventAttendees($groupId, $eventId) {
    $query = $this->db->prepare('SELECT * FROM rsvp WHERE group_id = :group_id AND event_id = :event_id');
    $query->execute([
      ':group_id' => $groupId,
      ':event_id' => $eventId
    ]);
    return $query->fetchAll();
  }

  public function userSubmittedRSVP($groupId, $eventId, $userId) {
    $query = $this->db->prepare('SELECT COUNT(*) as count FROM rsvp WHERE group_id = :group_id AND event_id = :event_id AND user_id = :user_id');
    $query->execute([
      ':group_id' => $groupId,
      ':event_id' => $eventId,
      ':user_id' => $userId
    ]);
    $row = $query->fetch();
    return ($row['count'] == 1);
  }

  private function addEventAttendee($groupId, $eventId, $userId, $rsvp) {
    $cols = 'group_id, event_id, user_id, rsvp, timestamp';
    $vals = ':group_id, :event_id, :user_id, :rsvp, :timestamp';
    $query = $this->db->prepare("INSERT INTO rsvp ($cols) VALUES ($vals)");
    $query->execute([
      ':group_id' => $groupId,
      ':event_id' => $eventId,
      ':user_id' => $userId,
      ':rsvp' => $rsvp,
      ':timestamp' => date('c')
    ]);
    return ($query->rowCount() === 1);
  }

  private function updateEventAttendee($groupId, $eventId, $userId, $rsvp) {
    $query = $this->db->prepare("UPDATE rsvp SET rsvp = :rsvp, timestamp = :timestamp WHERE group_id = :group_id AND event_id = :event_id AND user_id = :user_id");
    $query->execute([
      ':group_id' => $groupId,
      ':event_id' => $eventId,
      ':user_id' => $userId,
      ':rsvp' => $rsvp,
      ':timestamp' => date('c')
    ]);
    return ($query->rowCount() === 1);
  }

  public function submitRSVP($groupId, $eventId, $userId, $rsvp) {
    if (! $this->userSubmittedRSVP($groupId, $eventId, $userId)) {
      return $this->addEventAttendee($groupId, $eventId, $userId, $rsvp);
    } else {
      return $this->updateEventAttendee($groupId, $eventId, $userId, $rsvp);
    }
  }

}