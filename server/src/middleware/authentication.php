<?php

namespace Middleware;

class Authentication {

  public function __construct($container) {
    $this->db = $container['db'];
    $this->container = $container;
  }

  public function authenticate($id, $password) {
    // TODO: Move authentication out into a user service class
    $query = $this->db->prepare('SELECT COUNT(*) AS count FROM `users` WHERE id = :id AND password = :password');
    $query->execute([':id' => $id, ':password' => $password]);
    $row = $query->fetch();
    return ($row['count'] == 1);
  }

  public function __invoke($request, $response, $next) {
    $response = $response->withHeader('WWW-Authenticate','Basic realm="protected"');
    $authorization = $request->getHeaderLine('Authorization');
    $authenticated = false;
    if ($authorization) {
      list ($authType, $code) = explode(' ', $authorization);
      list ($user, $passwd) = explode(':', base64_decode($code));
      $authenticated = $this->authenticate($user, $passwd);
      if ($authenticated) {
        $this->container['identity'] = [ 'id' => $user, 'name' => $user ];
      }
    }
    if (! $authenticated) {
      $response = $response->withStatus(401);
      $response->getBody()->write('Access denied.');
      return $response;
    }
    
    $response = $next($request, $response);
    return $response;
  }

}