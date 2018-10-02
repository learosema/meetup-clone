<?php

namespace Tests\Functional;


class ApiTest extends BaseTestCase
{

  /**
   * Populate the in-memory database with test data.
   * @param $c the application container
   */
  protected function prepareTestData($c) {
    $c->userService->addUser([
      'id' => 'lea',
      'name' => 'Lea Rosema',
      'password' => 'lea123',
      'email' => 'lea@meetup-clone.lo',
      'role' => 'user'
    ], true);
    $c->userService->addUser([
      'id' => 'bot',
      'name' => 'Testing Bot',
      'password' => 'bot',
      'email' => 'bot@meetup-clone.lo',
      'role' => 'user'
    ], true);
    $c->groupService->createGroup([
      'id' => 'test',
      'name' => 'Test',
      'description' => 'test'
    ]);
    $c->groupService->addMember('test', 'lea', 'admin');
    $c->eventService->addGroupEvent([
      'id' => 'test-1',
      'group_id' => 'test',
      'name' => 'Test Event 1',
      'description' => 'Lorem ipsum dolor sit amet consectetur, adipisicing elit.',
      'location' => 'Dummy Place',
      'address' => 'Dummy Address',
      'lat' => '0',
      'lon' => '0',
      'date' => '2018-10-31'
    ]);
  }

  // The index route should redirect to the Swagger OpenAPI documentation
  public function testGetIndex()
  {
    $response = $this->runApp('GET', '/');
    $this->assertEquals(301, $response->getStatusCode());
  }

  // POST / should return Method not allowed
  public function testPostHomepageNotAllowed()
  {
    $response = $this->runApp('POST', '/', ['test']);
    $this->assertEquals(405, $response->getStatusCode());
    $this->assertContains('Method not allowed', (string)$response->getBody());
  }

  // GET /auth with wrong credentials should return 401
  public function testWrongPassword() {
    $response = $this->runApp('GET', '/auth', null, ['user' => 'admin', 'password' => 'x']);
    $this->assertEquals(401, $response->getStatusCode());
  }

  // GET /users should return an array of users
  public function testGetUsers() {
    $response = $this->runApp('GET', '/users');
    $this->assertEquals(200, $response->getStatusCode());
    $data = json_decode($response->getBody());
    $this->assertEquals(3, count($data));
    $this->assertEquals('Administrator', $data[0]->name);
  }

  // POST /user/{id} should create a new user
  public function testPostUser() {
    $response = $this->runApp('POST', '/user/sarah', [
      'password' => 'sarah',
      'name' => 'Sarah',
      'email' => 'sarah@sarah.de',
    ]);
    $this->assertEquals(200, $response->getStatusCode());
    $data = json_decode($response->getBody());
  }

  // POST /user/{id} with existing id should return 409 duplicate entry
  public function testPostExistingUser() {
    $response = $this->runApp('POST', '/user/lea', [
      'password' => 'lea',
      'name' => 'Lea',
      'email' => 'lea@lea.de',
    ]);
    $this->assertEquals(409, $response->getStatusCode());
    $data = json_decode($response->getBody());
  }

  // PUT /user/{id} should update the user
  public function testPutUser() {
    $response = $this->runApp('PUT', '/user/admin', [
      'name' => 'Admin',
      'password' => 'admin',
      'email' => 'admin@admin.local'
    ], ['user' => 'admin', 'password' => 'admin']);
    $this->assertEquals(200, $response->getStatusCode());
    $data = json_decode($response->getBody());
    $this->assertEquals('User updated.', $data->response);
  }

  // GET /groups should return an array of groups
  public function testGetGroups() {
    $response = $this->runApp('GET', '/groups');
    $this->assertEquals(200, $response->getStatusCode());
  }

  // POST /group/{id} should create a new group
  public function testPostGroup() {
    $response = $this->runApp('POST', '/group/javascript', [
      'name' => 'JavaScript Community Group',
      'description' => 'Lorem ipsum dolor sit amet consectetur, adipisicing elit.'
    ], ['user' => 'admin', 'password' => 'admin']);
    $this->assertEquals(200, $response->getStatusCode());
  }

  // PUT /group/{id} should update group with the given id
  public function testPutGroup() {
    $response = $this->runApp('PUT', '/group/test', [
      'name' => 'Test Group',
      'description' => 'Lorem ipsum dolor sit amet consectetur, adipisicing elit.'
    ], ['user' => 'admin', 'password' => 'admin']);
    $this->assertEquals(200, $response->getStatusCode());
  }
  
  // DELETE /group/{id} should delete group with the given id
  public function testDeleteGroup() {
    $response = $this->runApp('DELETE', '/group/test', null, ['user' => 'admin', 'password' => 'admin']);
    $this->assertEquals(200, $response->getStatusCode());
  }

  // GET /group/{id}/members should return an array of group members
  public function testGetGroupMembers() {
    $response = $this->runApp('GET', '/group/test/members');
    $this->assertEquals(200, $response->getStatusCode());
  }

  // POST /group/{id}/member/{mid} should add a group member
  public function testAddGroupMember() {
    $response = $this->runApp('POST', '/group/test/members/bot', null, ['user' => 'bot', 'password' => 'bot']);
    $this->assertEquals(200, $response->getStatusCode());
  }
  
  // DELETE /group/{id}/member/{mid} should delete a group member
  public function testDeleteGroupMember() {
    $response = $this->runApp('DELETE', '/group/test/members/lea', null, ['user' => 'lea', 'password' => 'lea123']);
    $this->assertEquals(200, $response->getStatusCode());
  }

  // GET /group/{id}/events should return an array of group members
  public function testGetGroupEvents() {
    $response = $this->runApp('GET', '/group/test/events');
    $this->assertEquals(200, $response->getStatusCode());
  }

  // POST /group/{id}/event/{id} should create an event
  public function testCreateGroupEvent() {
    $response = $this->runApp('POST', '/group/test/event/test-2', [
      'id' => 'test-2',
      'group_id' => 'test',
      'name' => 'Test Event...',
      'description' => 'Lorem ipsum dolor sit amet consectetur, adipisicing elit.',
      'location' => 'Dummy Place',
      'address' => 'Dummy Address',
      'lat' => '0',
      'lon' => '0',
      'date' => '2018-10-31'
    ], ['user' => 'lea', 'password' => 'lea123']);
    $this->assertEquals(200, $response->getStatusCode());
  }


}