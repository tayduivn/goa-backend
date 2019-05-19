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
    } else if ($payment) {
      $paypalClient = $this->gateWayPaypal()->clientToken()->generate();
      return $this->handleRequest($response, 200, '', ['paypal_client' => $paypalClient]);
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

    $typePayment = $request_body['type_payment'];

    $code               = $request_body['code'];
    $processor          = $request_body['processor'];
    $processor_trans_id = $request_body['processor_trans_id'];
    $cc_num             = $request_body['cc_num'];
    $cc_type            = $request_body['cc_type'];

    $subtotal = $request_body['subtotal'];
    $total    = $request_body['total'];
    $user_id  = $request_body['user_id'];

    try {
      if (isset($typePayment)) {
        switch ($typePayment['name']) {
          case 'Paypal':
            $result = $this->sendPaypal($total, $payloadPaypal);
            if ($result->success) {
              var_dump("Success ID: " . $result->transaction->id);
            } else {
              var_dump("Error Message: " . $result->message);
            }
            break;
          case 'Credit card':
            if (isset($tokenStripe)) {
              list($cc_num, $cc_type) = $this->sendStripe($tokenStripe, $total);
            } else {
              return $this->handleRequest($response, 400, 'Incorrect data 1');
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
        return $this->handleRequest($response, 400, 'Incorrect data 2');
      }

      if (!isset($cart_id) AND !isset($subtotal) AND !isset($total) AND !isset($user_id) AND !isset($code)
        AND !isset($processor) AND !isset($processor_trans_id) AND !isset($cc_num) AND !isset($cc_type)
        AND !isset($start_date) AND !isset($end_date)) {
        return $this->handleRequest($response, 400, 'Datos incorrectos');
      }

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
    } catch (\Throwable $e) { // use \Exception in PHP < 7.0
      $this->db->rollBack();
      throw $e;
    }

    $this->db->commit();

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

  /**
   * @param $tokenStripe
   * @param $total
   * @return array
   */
  public function sendStripe($tokenStripe, $total) {
    \Stripe\Stripe::setApiKey('sk_test_SwAD8JBSO0iH4W46hRXUj1CD00qBWuhkuk');
    $customer = \Stripe\Customer::create([
                                           'email'  => $tokenStripe['email'],
                                           'source' => $tokenStripe['id'],
                                         ]);

    $charge = \Stripe\Charge::create([
                                       'customer'    => $customer->id,
                                       'description' => 'Custom t-shirt',
                                       'amount'      => $total,
                                       'currency'    => 'usd',
                                     ]);

    $cc_num  = $tokenStripe['card']['last4'];
    $cc_type = $tokenStripe['card']['brand'];
    return array($cc_num, $cc_type);
  }

  /**
   * @param $total
   * @return mixed
   */
  public function sendPaypal($total, $payloadPaypal) {
    $options = [
      "amount"             => $total,
      'merchantAccountId'  => 'USD',
      "paymentMethodNonce" => $_POST['payment_method_nonce'],
      "orderId"            => $_POST['Mapped to PayPal Invoice Number'],
      "descriptor"         => [
        "name" => "Descriptor displayed in customer CC statements. 22 char max"
      ],
      "shipping"           => [
        "firstName"         => "Jen",
        "lastName"          => "Smith",
        "company"           => "Braintree",
        "streetAddress"     => "1 E 1st St",
        "extendedAddress"   => "Suite 403",
        "locality"          => "Bartlett",
        "region"            => "IL",
        "postalCode"        => "60103",
        "countryCodeAlpha2" => "US"
      ],
      "options"            => [
        "paypal" => [
          "customField" => $_POST["PayPal custom field"],
          "description" => $_POST["Description for PayPal email receipt"]
        ],
      ]
    ];
    return $this->gateWayPaypal()->transaction()->sale($options);
  }

}
