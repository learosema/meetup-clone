<?php
return [
  'settings' => [
    'displayErrorDetails' => true, // set to false in production
    'addContentLengthHeader' => false, // Allow the web server to send the content-length header
    'db' => [
      'connection' => 'sqlite:' . __DIR__ . '/../db/meetup-clone.db',
      'user' => null,
      'password' => null
    ],
    'cors' => [
      'https://terabaud.de',
      'https://terabaud.github.io',
      'http://localhost:8080/',
      'http://localhost:8081/',
      'http://localhost:1234/'
    ]
  ]
];
