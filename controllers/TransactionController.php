<?php

namespace App\Controller;

use Psr\Container\ContainerInterface as ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TransactionController extends HandleRequest {

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
      $statement = $this->db->prepare("SELECT * FROM `transaction` WHERE id = :id AND active != '0' ORDER BY " . $order);
      $statement->execute(['id' => $id]);
    } else {
      $statement = $this->db->prepare("SELECT * FROM `transaction` WHERE active != '0'");
      $statement->execute();
    }
    return $this->getSendResponse($response, $statement);
  }

  public function register(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();

    $cart_id = $request_body['cart_id'];

    $code               = $request_body['code'];
    $processor          = $request_body['processor'];
    $processor_trans_id = $request_body['processor_trans_id'];
    $cc_num             = $request_body['cc_num'];
    $cc_type            = $request_body['cc_type'];
    $start_date         = $request_body['start_date'];
    $end_date           = $request_body['end_date'];

    $subtotal = $request_body['subtotal'];
    $total    = $request_body['total'];
    $user_id  = $request_body['user_id'];

    if (!isset($cart_id) AND !isset($subtotal) AND !isset($total) AND !isset($user_id) AND !isset($code)
      AND !isset($processor) AND !isset($processor_trans_id) AND !isset($cc_num) AND !isset($cc_type)
      AND !isset($start_date) AND !isset($end_date)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    /* TODO: add date, maybe? */
    /* TODO: how to make rollback fail query in PDO */
    $query   = "INSERT INTO transaction (`code`, `processor`, `processor_trans_id`, `cc_num`, `cc_type`) 
                VALUES (:code, :processor, :processor_trans_id, :cc_num, :cc_type)";
    $prepare = $this->db->prepare($query);
    $result  = $prepare->execute([
                                   'code'               => $code,
                                   'processor'          => $processor,
                                   'processor_trans_id' => $processor_trans_id,
                                   'cc_num'             => $cc_num,
                                   'cc_type'            => $cc_type,
                                 ]);

    $transaction_id = $this->db->lastInsertId();

    if ($result) {
      $result = $this->updateCart($cart_id);

      if ($result) {
        if ($this->isAlreadyCartOrder($cart_id, $this->db)) {
          return $this->handleRequest($response, 409, 'Cart is already exist');
        } else {
          $query   = "INSERT INTO `order` (`subtotal`, `total`, `user_id`, `cart_id`, `transaction_id`) 
                      VALUES(:subtotal, :total, :user_id, :cart_id, :transaction_id)";
          $prepare = $this->db->prepare($query);
          $result  = $prepare->execute([
                                         'subtotal'       => $subtotal,
                                         'total'          => $total,
                                         'user_id'        => $user_id,
                                         'cart_id'        => $cart_id,
                                         'transaction_id' => $transaction_id,
                                       ]);
          return $this->postSendResponse($response, $result, 'Datos registrados');
        }
      }
    }

    return $this->handleRequest($response, 400);
  }

  public function update(Request $request, Response $response, $args) {
    $request_body       = $request->getParsedBody();
    $id                 = $request_body['id'];
    $processor          = $request_body['processor'];
    $processor_trans_id = $request_body['processor_trans_id'];
    $cc_num             = $request_body['cc_num'];
    $cc_type            = $request_body['cc_type'];

    if (!isset($code) and !isset($processor) and !isset($processor_trans_id) and !isset($cc_num) and !isset($cc_type)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    $prepare = $this->db->prepare(
      "UPDATE transaction 
        SET code = :code, processor = :processor, processor_trans_id = :processor_trans_id, cc_num = :cc_num, cc_type = :cc_type
        WHERE id = :id"
    );

    $result = $prepare->execute([
                                  'id'                 => $id,
                                  'code'               => $code,
                                  'processor'          => $processor,
                                  'processor_trans_id' => $processor_trans_id,
                                  'cc_num'             => $cc_num,
                                  'cc_type'            => $cc_type,
                                ]);

    return $this->postSendResponse($response, $result, 'Datos actualizados');
  }

  public function delete(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $id           = $request_body['id'];

    if (!isset($id)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    $statement = $this->db->prepare("SELECT * FROM transaction WHERE id = :id AND active != '0'");
    $statement->execute(['id' => $id]);
    $result = $statement->fetch();
    if (is_array($result)) {
      $prepare = $this->db->prepare(
        "UPDATE transaction SET active = :active WHERE id = :id"
      );
      $result  = $prepare->execute(['id' => $id, 'active' => 0]);

      return $this->postSendResponse($response, $result, 'Datos eliminados');
    } else {
      return $this->handleRequest($response, 404, "Información no encontrada");
    }
  }

  /**
   * @param $cart_id
   * @return array
   */
  public function updateCart($cart_id) {
    $prepare = $this->db->prepare("UPDATE cart SET status = :status WHERE id = :id");
    $result  = $prepare->execute(['id' => $cart_id, 'status' => 'checkout',]);

    var_dump($result);

    if ($result) {
      $query     = "SELECT cart.id, cart.status, cart.active, cart.inserted_at, cart.updated_at, cart.user_id, 
                    cp.id, cp.quantity, cp.inserted_at, cp.updated_at, cp.cart_id, cp.product_id
                    FROM cart INNER JOIN cart_products cp on cart.id = cp.cart_id
                    WHERE cart.active != '0' AND cart.id = :id";
      $statement = $this->db->prepare($query);
      $statement->execute(['id' => $cart_id]);
      $result = $statement->fetchAll();

      if (!empty($result) && is_array($result)) {

        foreach ($result as $index => $cartProduct) {
          $statement = $this->db->prepare("SELECT * FROM product WHERE product.active != '0' AND product.id = :id");
          $statement->execute(['id' => $cartProduct["product_id"]]);
          $resultProduct = $statement->fetchObject();

          if (!empty($resultProduct) && is_object($resultProduct)) {
            $quantity = $resultProduct->quantity - $cartProduct["quantity"];

            var_dump($quantity);

            $prepare = $this->db->prepare("UPDATE product SET quantity = :quantity WHERE id = :id");
            $result  = $prepare->execute(['id' => $cartProduct["product_id"], 'quantity' => $quantity,]);
          }
        }
      }
    }

    return $result;
  }

}
