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
    $id      = $request->getQueryParam('id');
    $order   = $request->getQueryParam('order', $default = 'ASC');
    $payment = $request->getQueryParam('payment', $default = false);

    if ($id !== null) {
      $statement = $this->db->prepare("SELECT * FROM `transaction` WHERE id = :id AND active != '0' ORDER BY " . $order);
      $statement->execute(['id' => $id]);
    } else if ($payment === 'Paypal') {
      $paypalClient = $this->gateWayPaypal($this->db)->clientToken()->generate();
      $statement = $this->db->prepare("SELECT * FROM payment");
      $statement->execute();
      $result = $statement->fetchAll();
      return $this->handleRequest($response, 200, '', ['paypal_client' => $paypalClient, 'production_paypal' => $result[0]['production_paypal']]);
    } else {
      $statement = $this->db->prepare("SELECT * FROM `transaction` WHERE active != '0'");
      $statement->execute();
    }
    return $this->getSendResponse($response, $statement);
  }

  public function register(Request $request, Response $response, $args) {
    $this->db->beginTransaction();

    $request_body  = $request->getParsedBody();
    $tokenStripe   = $request_body['token_stripe'];
    $payloadPaypal = $request_body['payload_paypal'];

    $cart_id = $request_body['cart_id'];

    $code               = $request_body['code'];
    $processor          = $request_body['processor'];
    $processor_trans_id = $request_body['processor_trans_id'];

    $subtotal = $request_body['subtotal'];
    $total    = $request_body['total'];
    $user_id  = $request_body['user_id'];

    try {
      if (isset($processor)) {
        switch ($processor) {
          case 'Paypal':
            $result = $this->postPaypal($this->db, $total, $processor, $payloadPaypal);
            if ($result->success) {
              $processor_trans_id = $result->transaction->id;
            } else {
              return $this->handleRequest($response, 400, "Error Message: " . $result->message);
            }
            break;
          case 'Credit card':
            if (isset($tokenStripe)) {
              $this->postStripe($this->db, $tokenStripe, $total);
            } else {
              return $this->handleRequest($response, 400, 'Incorrect data stripe');
            }
            break;
          case 'Amazon':
            /*$this->getOrderPaypal($typePayment->orderId);*/
            break;
          default:
            return $this->handleRequest($response, 400, 'Incorrect data');
            break;
        }
      } else {
        return $this->handleRequest($response, 400, 'Incorrect processor');
      }

      if (!isset($cart_id) AND !isset($subtotal) AND !isset($total) AND !isset($user_id) AND !isset($code)
        AND !isset($processor) AND !isset($processor_trans_id)) {
        return $this->handleRequest($response, 400, 'Incorrect data');
      }

      $query   = "INSERT INTO transaction (`processor`, `processor_trans_id`) VALUES (:processor, :processor_trans_id)";
      $prepare = $this->db->prepare($query);
      $result  = $prepare->execute(['processor' => $processor, 'processor_trans_id' => $processor_trans_id,]);

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

            $this->db->commit();
            return $this->postSendResponse($response, $result, 'Data register');
          }
        }
      }
    } catch (\Throwable $e) { // use \Exception in PHP < 7.0
      $this->db->rollBack();
      throw $e;
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

    $query   = "UPDATE transaction 
                SET code = :code, processor = :processor, processor_trans_id = :processor_trans_id, cc_num = :cc_num, cc_type = :cc_type
                WHERE id = :id";
    $prepare = $this->db->prepare($query);

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
      $prepare = $this->db->prepare("UPDATE transaction SET active = :active WHERE id = :id");
      $result  = $prepare->execute(['id' => $id, 'active' => 0]);

      return $this->postSendResponse($response, $result, 'Datos eliminados');
    } else {
      return $this->handleRequest($response, 404, "Información no encontrada");
    }
  }

  /**
   * @param $cart_id
   * @return bool
   */
  public function updateCart($cart_id) {
    $prepare = $this->db->prepare("UPDATE cart SET status = :status WHERE id = :id");
    $result  = $prepare->execute(['id' => $cart_id, 'status' => 'checkout',]);

    if ($result) {
      $query     = "SELECT cart.id, cart.status, cart.active, cart.inserted_at, cart.updated_at, cart.user_id, 
                    cp.id, cp.quantity, cp.inserted_at, cp.updated_at, cp.cart_id, cp.product_id
                    FROM cart INNER JOIN cart_products cp on cart.id = cp.cart_id
                    WHERE cart.active != '0' AND cart.id = :id";
      $statement = $this->db->prepare($query);
      $statement->execute(['id' => $cart_id]);
      $result = $statement->fetchAll();

      $userId = $result[0]['user_id'];

      if (!empty($result) && is_array($result)) {

        foreach ($result as $index => $cartProduct) {
          $statement = $this->db->prepare("SELECT * FROM product WHERE product.active != '0' AND product.id = :id");
          $statement->execute(['id' => $cartProduct["product_id"]]);
          $resultProduct = $statement->fetchObject();

          if (!empty($resultProduct) && is_object($resultProduct)) {
            $quantity = $resultProduct->quantity - $cartProduct["quantity"];

            $prepare = $this->db->prepare("UPDATE product SET quantity = :quantity WHERE id = :id");
            $result  = $prepare->execute(['id' => $cartProduct["product_id"], 'quantity' => $quantity,]);

            if ($result) {
              $prepare = $this->db->prepare("INSERT INTO cart (user_id) VALUES (:user_id)");
              $result  = $prepare->execute(['user_id' => $userId]);
              return $result;
            } else {
              return false;
            }
          } else {
            return false;
          }
        }
      } else {
        return false;
      }
    } else {
      return false;
    }
    return false;
  }

}
