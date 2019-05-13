<?php
// Application middleware

// e.g: $app->add(new \Slim\Csrf\Guard);

// for authentication
$app->add(new \Tuupola\Middleware\JwtAuthentication(
            [
              "path"      => "/api", /* or ["/api", "/admin"] */
              "attribute" => "decoded_token_data",
              "ignore"    => ["/api/public"],
              "secret"    => "supersecretkeyyoushouldnotcommittogithub",
              "algorithm" => ["HS256"],
              "secure"    => false,
              "error"     => function ($response, $arguments) {
                $data["status"]  = "error";
                $data["message"] = $arguments["message"];
                return $response
                  ->withHeader("Content-Type", "application/json")
                  ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
              }
            ])
);

$app->options('/{routes:.+}', function ($request, $response, $args) {
  return $response;
});

$app->add(function ($req, $res, $next) {
  $response = $next($req, $res);
  return $response
    ->withHeader('Access-Control-Allow-Origin', '*')
    ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, token')
    ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});
