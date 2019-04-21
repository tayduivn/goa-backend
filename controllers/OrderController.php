<?php

namespace App\Controller;

use Psr\Container\ContainerInterface as ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class OrderController extends HandleRequest {

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
      $statement = $this->db->prepare("SELECT * FROM `order` WHERE id = :id AND active != '0' ORDER BY " . $order);
      $statement->execute(['id' => $id]);
    } else {
      $statement = $this->db->prepare("SELECT * FROM `order` WHERE active != '0'");
      $statement->execute();
    }
    return $this->getSendResponse($response, $statement);
  }

  public function register(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $subtotal     = $request_body['subtotal'];
    $total        = $request_body['total'];
    $user_id      = $request_body['user_id'];
    $cart_id      = $request_body['cart_id'];

    if (!isset($subtotal) && !isset($total) && !isset($user_id) && !isset($cart_id)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }
    $prepare = $this->db->prepare(
      "INSERT INTO `order` (`subtotal`, `total`, `user_id`, `cart_id`) VALUES(:subtotal, :total, :user_id, :cart_id)"
    );
    $result  = $prepare->execute([
                                   'subtotal' => $subtotal,
                                   'total'    => $total,
                                   'user_id'  => $user_id,
                                   'cart_id'  => $cart_id,
                                 ]);

    return $this->postSendResponse($response, $result, 'Datos registrados');
  }

  public function update(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $id           = $request_body['id'];
    $subtotal     = $request_body['subtotal'];
    $total        = $request_body['total'];
    $user_id      = $request_body['user_id'];
    $cart_id      = $request_body['cart_id'];

    if (!isset($id) && !isset($subtotal) && !isset($total) && !isset($user_id) && !isset($cart_id)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    $prepare = $this->db->prepare(
      "UPDATE `order` 
      SET subtotal = :subtotal, total = :total, user_id = :user_id, cart_id = :cart_id
      WHERE id = :id"
    );

    $result = $prepare->execute([
                                  'id'       => $id,
                                  'subtotal' => $subtotal,
                                  'total'    => $total,
                                  'user_id'  => $user_id,
                                  'cart_id'  => $cart_id,
                                ]);

    return $this->postSendResponse($response, $result, 'Datos actualizados');
  }

  public function delete(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $id           = $request_body['id'];

    if (!isset($id)) {
      return $this->handleRequest($response, 400, 'Missing fields id');
    }

    $statement = $this->db->prepare("SELECT * FROM `order` WHERE id = :id AND active != '0'");
    $statement->execute(['id' => $id]);
    $result = $statement->fetch();
    if (is_array($result)) {
      $prepare = $this->db->prepare(
        "UPDATE `order` SET status = :active WHERE id = :id"
      );
      $result  = $prepare->execute(['id' => $id, 'active' => 0]);
      return $this->postSendResponse($response, $result, 'Datos eliminados');
    } else {
      return $this->handleRequest($response, 404, "Información no encontrada");
    }
  }

}
