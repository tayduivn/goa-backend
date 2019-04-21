<?php
$db = [
  'dev' => [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'dbname' => 'goa',
  ],
  'prod' => [
    'host' => '',
    'user' => '',
    'pass' => '',
    'dbname' => '',
  ],
];

return [
  'settings' => [
    "determineRouteBeforeAppMiddleware" => true,
    'displayErrorDetails'               => true, // set to false in production
    'addContentLengthHeader'            => false, // Allow the web server to send the content-length header

    // Renderer settings
    'renderer'                          => [
      'template_path' => __DIR__ . '/../templates/',
    ],

    // Monolog settings
    'logger'                            => [
      'name'  => 'slim-app',
      'path'  => __DIR__ . '/../logs/app.log',
      'level' => \Monolog\Logger::DEBUG,
    ],

    'db'  => [
      'host'   => $db['dev']['host'],
      'user'   => $db['dev']['user'],
      'pass'   => $db['dev']['pass'],
      'dbname' => $db['dev']['dbname']
    ],

    // jwt settings
    "jwt" => [
      'secret' => 'supersecretkeyyoushouldnotcommittogithub'
    ]
  ],
];
