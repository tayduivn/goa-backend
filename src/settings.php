<?php
$db = [
  'dev' => [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'dbname' => 'goa',
  ],
  'prod' => [
    'host' => 'localhost',
    'user' => 'garden12_america',
    'pass' => 'ILbwLWh02dh5',
    'dbname' => 'garden12_america',
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
      'host'   => $db['prod']['host'],
      'user'   => $db['prod']['user'],
      'pass'   => $db['prod']['pass'],
      'dbname' => $db['prod']['dbname']
    ],

    // jwt settings
    "jwt" => [
      'secret' => 'supersecretkeyyoushouldnotcommittogithub'
    ]
  ],
];
