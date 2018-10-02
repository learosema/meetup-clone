<?php

namespace Services;


class EventService {

  public function __construct($db) {
    $this->db = $db;
  }

  public function getGroupEvents($groupId) {
    $query = $this->db->prepare('SELECT * FROM group_events WHERE group_id = :group_id');
    $query->execute([':group_id' => $groupId]);
    return ($query->fetchAll());
  }

  public function getGroupEvent($groupId, $eventId) {
    $query = $this->db->prepare('SELECT * FROM group_events WHERE group_id = :group_id AND id = :event_id');
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
    $query = $this->db->prepare("INSERT INTO group_events ($strCols) VALUES ($strVals)");
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

  public function deleteGroupEvent($groupId, $eventId) {
    $query = $this->db->prepare('DELETE FROM group_events WHERE id = :id AND group_id = :group_id');
    $query->execute([
      ':id' => $eventId,
      ':group_id' => $groupId
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