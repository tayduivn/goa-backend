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
    $id     = $request->getQueryParam('id');
    $order  = $request->getQueryParam('order', $default = 'ASC');
    $userId = $request->getQueryParam('userId');
    $cartId = $request->getQueryParam('cartId');
    $status = $request->getQueryParam('status', $default = 'Nuevo');

    if ($id !== null) {
      $query     = "SELECT * FROM `order` WHERE id = :id AND active != '0' ORDER BY inserted_at ASC";
      $statement = $this->db->prepare($query);
      $statement->execute(['id' => $id]);

    } elseif ($userId !== null && $cartId === null) {
      $query     = "SELECT * 
                    FROM `order`
                    WHERE `order`.active != '0' AND user_id = :userId AND status = :status";
      $statement = $this->db->prepare($query);
      $statement->execute(['status' => $status, 'userId' => $userId]);

    } elseif ($userId !== null && $cartId !== null) {
      $query     = "SELECT `order`.id AS order_id, `order`.subtotal, `order`.total, `order`.active, `order`.status, `order`.updated_at AS order_updated_at, 
                    `order`.user_id, `order`.cart_id AS order_cart_id, `order`.inserted_at AS order_inserted_at, 
                    u.id, u.email, u.first_name, u.last_name, u.password, u.address, u.phone, u.active, u.role_id, 
                    u.inserted_at, u.updated_at 
                    FROM `order` INNER JOIN user u on `order`.user_id = u.id
                    WHERE `order`.active != '0' AND `order`.user_id = :userId AND cart_id = :cartId";
      $statement = $this->db->prepare($query);
      $statement->execute(['userId' => $userId, 'cartId' => $cartId]);
      $result = $statement->fetchAll();

      var_dump($result[0]['order_cart_id']);

      if (is_array($result)) {
        $query     = "SELECT cart.id, cart.price, cart.quantity, cart.active, cart.inserted_at, cart.updated_at, 
                      cart.user_id, cart.product_id, 
                      p.id, p.sku, p.name, p.description_short, p.description_one, p.description_two, p.preparation, 
                      p.regular_price, p.quantity, p.active, p.inserted_at, p.updated_at, p.user_id
                    FROM cart INNER JOIN product p on cart.product_id = p.id
                    WHERE cart.id = :cartId AND cart.active != 0";
        $statement = $this->db->prepare($query);
        $statement->execute(['userId' => $userId, 'cartId' => $result[0]['order_cart_id']]);
        $resultCarts = $statement->fetchAll();

        if (is_array($resultCarts) and !empty($resultCarts)) {
          $result[0]['products'] = $resultCarts;
        } else {
          $result[0]['products'] = [];
        }
        return $this->handleRequest($response, 200, '', $result);
      } else {
        return $this->handleRequest($response, 204, '', []);
      }
    } else {
      $query     = "SELECT * 
                    FROM `order`
                    WHERE `order`.active != '0' AND status = :status";
      $statement = $this->db->prepare($query);
      $statement->execute(['status' => $status]);
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
