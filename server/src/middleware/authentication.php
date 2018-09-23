<?php

namespace Middleware;

class Authentication {

  public function __construct($container) {
    $this->userService = $container->userService;
    $this->container = $container;
  }

  public function authenticate($id, $password) {
    return ($this->userService->validate($id, $password));
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
        $this->container['identity'] = $authenticated;
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