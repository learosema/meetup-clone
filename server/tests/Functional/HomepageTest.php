<?php

namespace Tests\Functional;

class HomepageTest extends BaseTestCase
{

  protected function prepareTestData($c) {
    $c->groupService->createGroup([
      'id' => 'test',
      'name' => 'Test',
      'description' => 'test'
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

  // GET /users should return an array of users
  public function testGetUsers() {
    $response = $this->runApp('GET', '/users');
    $this->assertEquals(200, $response->getStatusCode());
    $data = json_decode($response->getBody());
    $this->assertEquals(1, count($data));
    $this->assertEquals('Administrator', $data[0]->name);
  }

  // POST /user/{id} should create a new user
  public function testPostUser() {
    $response = $this->runApp('POST', '/user/lea', [
      'password' => 'lea',
      'name' => 'Lea Rosema',
      'email' => 'lea@lea.de',
    ]);
    $this->assertEquals(200, $response->getStatusCode());
    $data = json_decode($response->getBody());
  }

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

  public function testGetGroups() {
    $response = $this->runApp('GET', '/groups');
    $this->assertEquals(200, $response->getStatusCode());
  }

  public function testPostGroup() {
    $response = $this->runApp('POST', '/group/javascript', [
      'name' => 'JavaScript Community Group',
      'description' => 'Lorem ipsum dolor sit amet consectetur, adipisicing elit.'
    ], ['user' => 'admin', 'password' => 'admin']);
    $this->assertEquals(200, $response->getStatusCode());
  }

  public function testPutGroup() {
    $response = $this->runApp('PUT', '/group/test', [
      'name' => 'Test Group',
      'description' => 'Lorem ipsum dolor sit amet consectetur, adipisicing elit.'
    ], ['user' => 'admin', 'password' => 'admin']);
    $this->assertEquals(200, $response->getStatusCode());
  }

  public function testDeleteGroup() {
    $response = $this->runApp('DELETE', '/group/test', null, ['user' => 'admin', 'password' => 'admin']);
    $this->assertEquals(200, $response->getStatusCode());
  }
 
}