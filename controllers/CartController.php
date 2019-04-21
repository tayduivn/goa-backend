<?php

namespace App\Controller;

use Psr\Container\ContainerInterface as ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CartController extends HandleRequest {

  private $db       = null;
  private $logger   = null;
  private $settings = null;
  private $session  = null;
  private $upload   = null;

  public function __construct(ContainerInterface $container) {
    $this->db       = $container->get('db');
    $this->logger   = $container->get('logger');
    $this->settings = $container->get('settings');
    $this->session  = $container->get('session');
    $this->upload   = $container->get('upload_directory');
  }

  public function getAll(Request $request, Response $response, $args) {
    $id    = $request->getQueryParam('id');
    $order = $request->getQueryParam('order', $default = 'ASC');

    if ($id !== null) {
      $statement = $this->db->prepare("SELECT * FROM cart WHERE id = :id AND active != '0' ORDER BY " . $order);
      $statement->execute(['id' => $id]);
    } else {
      $statement = $this->db->prepare("SELECT * FROM cart WHERE active != '0'");
      $statement->execute();
    }
    return $this->getSendResponse($response, $statement);
  }

  public function register(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $price        = $request_body['price'];
    $quantity     = $request_body['quantity'];
    $user_id      = $request_body['user_id'];
    $product_id   = $request_body['product_id'];

    if (!isset($price) && !isset($quantity)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }
    $prepare = $this->db->prepare(
      "INSERT INTO cart (`price`, `quantity`, `user_id`, `product_id`) 
                    VALUES (:price, :quantity, :user_id, :product_id)"
    );
    $result  = $prepare->execute([
                                   'price'      => $price,
                                   'quantity'   => $quantity,
                                   'user_id'    => $user_id,
                                   'product_id' => $product_id,
                                 ]);

    return $this->postSendResponse($response, $result, 'Datos registrados');
  }

  public function update(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $id           = $request_body['id'];
    $price        = $request_body['price'];
    $quantity     = $request_body['quantity'];
    $user_id      = $request_body['user_id'];
    $product_id   = $request_body['product_id'];

    if (!isset($id) && !isset($price) && !isset($quantity)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    $prepare = $this->db->prepare(
      "UPDATE cart 
      SET price = :price, quantity = :quantity, user_id = :user_id, product_id = :product_id
      WHERE id = :id"
    );

    $result = $prepare->execute([
                                  'id'         => $id,
                                  'price'      => $price,
                                  'quantity'   => $quantity,
                                  'user_id'    => $user_id,
                                  'product_id' => $product_id,
                                ]);

    return $this->postSendResponse($response, $result, 'Datos actualizados');
  }

  public function delete(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $id           = $request_body['id'];

    if (!isset($id)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    $statement = $this->db->prepare("SELECT * FROM cart WHERE id = :id AND active != '0'");
    $statement->execute(['id' => $id]);
    $result = $statement->fetch();
    if (is_array($result)) {
      $prepare = $this->db->prepare("UPDATE cart SET active = :active WHERE id = :id");
      $result  = $prepare->execute(['id' => $id, 'active' => 0]);
      return $this->postSendResponse($response, $result, 'Datos eliminados');
    } else {
      return $this->handleRequest($response, 404, "Información no encontrada");
    }
  }

}
