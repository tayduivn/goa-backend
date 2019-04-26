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
                                   'message'    => 'Success',
                                   'statusCode' => 200,
                                   'data'       => $routes,
                                   'error'      => false
                                 ], 200);
    });

    $app->post('/users/login', 'App\Controller\UserController:login');
    $app->post('/users/register', 'App\Controller\UserController:register');
    $app->post('/users/forgot', 'App\Controller\UserController:forgot');

    $app->get('/categories', 'App\Controller\CategoryController:getAll');

    $app->get('/images', 'App\Controller\ImageController:getAll');

    $app->get('/reviews', 'App\Controller\ReviewController:getAll');
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

  $app->post('/categories', 'App\Controller\CategoryController:register');
  $app->put('/categories', 'App\Controller\CategoryController:update');
  $app->delete('/categories', 'App\Controller\CategoryController:delete');

  $app->post('/categories/products', 'App\Controller\ProductCategoryController:register');
  $app->put('/categories/products', 'App\Controller\ProductCategoryController:update');
  $app->delete('/categories/products', 'App\Controller\ProductCategoryController:delete');

  $app->post('/images/register', 'App\Controller\ImageController:register');
  $app->post('/images/update', 'App\Controller\ImageController:update');
  $app->delete('/images', 'App\Controller\ImageController:delete');

  $app->post('/reviews', 'App\Controller\ReviewController:register');
  $app->put('/reviews', 'App\Controller\ReviewController:update');
  $app->delete('/reviews', 'App\Controller\ReviewController:delete');

  $app->get('/orders', 'App\Controller\OrderController:getAll');
  $app->post('/orders', 'App\Controller\OrderController:register');
  $app->put('/orders', 'App\Controller\OrderController:update');

  $app->get('/carts', 'App\Controller\ContentController:getAll');
  $app->post('/carts', 'App\Controller\ContentController:register');
  $app->put('/carts', 'App\Controller\ContentController:update');
  $app->delete('/carts', 'App\Controller\ContentController:delete');
});

// fallback for home page
$app->get('/[{name}]', function ($request, $response, $args) {
  // Sample log message
  $this->logger->info("Slim-Skeleton '/' route");

  // Render index view
  return $this->renderer->render($response, 'index.phtml', $args);
});
