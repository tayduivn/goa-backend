<?php
// DIC configuration

$container = $app->getContainer();

// monolog
$container['logger'] = function ($c) {
  $settings = $c->get('settings')['logger'];
  $logger   = new Monolog\Logger($settings['name']);
  $logger->pushProcessor(new Monolog\Processor\UidProcessor());
  $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
  return $logger;
};

// container
$container['db'] = function ($c) {
  $db  = $c['settings']['db'];
  $pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'], $db['user'], $db['pass']);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  return $pdo;
};

// view renderer
$container['renderer'] = function ($c) {
  $settings = $c->get('settings')['renderer'];
  return new Slim\Views\PhpRenderer($settings['template_path']);
};

$container['session'] = function ($c) {
  return new \SlimSession\Helper;
};

$container['notAllowedHandler'] = function ($c) {
  return function ($request, $response, $methods) use ($c) {
    return $response->withStatus(405)
      ->withHeader('Allow', implode(', ', $methods))
      ->withHeader('Content-Type', 'application/json')
      ->write('Method must be one of: ' . implode(', ', $methods));
  };
};

$container['upload_directory'] = __DIR__ . '/uploads';
