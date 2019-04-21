<?php

$app->get('/src/uploads/{image}', function ($request, $response, $args) use ($app) {
  $file = __DIR__ . "/uploads/" . $args['image'];
  if (!file_exists($file)) {
    die("file:$file");
  }
  $image = file_get_contents($file);
  if ($image === false) {
    die("error getting image");
  }
  var_dump($file);
  $response->write($image);
  return $response->withHeader('Content-Type', FILEINFO_MIME_TYPE);
});

$app->group('/api', function () use ($app) {
  $app->group('/public', function () use ($app) {

    $app->get('/endpoints', function ($request, $response, $args) use ($app) {
      $routes = array_reduce($app->getContainer()->get('router')->getRoutes(), function ($target, $route) {
        $target[$route->getPattern()] = "";
        return $target;
      }, []);
      return $response->withJson([
                                   'statusCode' => 200,
                                   'message'    => 'Success',
                                   'data'       => $routes,
                                   'error'      => false
                                 ], 200);
    });

    $app->get('/users/forgot/{email}', 'App\Controller\UserController:forgot');
    $app->post('/users/login', 'App\Controller\UserController:login');
    $app->post('/users/register', 'App\Controller\UserController:register');

    $app->get('/categories', 'App\Controller\CategoryController:getAll');

    $app->get('/images', 'App\Controller\ImagesController:getAll');
  });

  $app->get('/users', 'App\Controller\UserController:getAll');
  $app->put('/users', 'App\Controller\UserController:update');
  $app->put('/users/password', 'App\Controller\UserController:updatePassword');
  $app->delete('/users', 'App\Controller\UserController:delete');

  $app->get('/transactions', 'App\Controller\TransactionController:getAll');
  $app->post('/transactions', 'App\Controller\TransactionController:register');
  $app->put('/transactions', 'App\Controller\TransactionController:update');
  $app->delete('/transactions', 'App\Controller\TransactionController:delete');

  $app->get('/products', 'App\Controller\ProductController:getAll');
  $app->post('/products', 'App\Controller\ProductController:register');
  $app->put('/products', 'App\Controller\ProductController:update');
  $app->delete('/products', 'App\Controller\ProductController:delete');

  $app->get('/orders', 'App\Controller\OrderController:getAll');
  $app->post('/orders', 'App\Controller\OrderController:register');
  $app->put('/orders', 'App\Controller\OrderController:update');

  $app->get('/carts', 'App\Controller\ContentController:getAll');
  $app->post('/carts', 'App\Controller\ContentController:register');
  $app->put('/carts', 'App\Controller\ContentController:update');
  $app->delete('/delete', 'App\Controller\ContentController:delete');

  $app->post('/images', 'App\Controller\ImagesController:post');
  $app->put('/images', 'App\Controller\ImagesController:update');
  $app->delete('/images', 'App\Controller\ImagesController:delete');

  $app->post('/categories', 'App\Controller\CategoryController:post');
  $app->put('/categories', 'App\Controller\CategoryController:update');
  $app->delete('/categories', 'App\Controller\CategoryController:delete');
});

// fallback for home page
$app->get('/[{name}]', function ($request, $response, $args) {
  // Sample log message
  $this->logger->info("Slim-Skeleton '/' route");

  // Render index view
  return $this->renderer->render($response, 'index.phtml', $args);
});
