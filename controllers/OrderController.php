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

  public function getId(Request $request, Response $response, $args) {
    $idorder = (int)$args['id'];
    if (empty($idorder))
      return $this->handleRequest($response, 400, 'Requerid id');

    $statement = $this->db->prepare("SELECT * FROM `order` INNER JOIN user ON `order`.user_iduser = user.iduser WHERE idorder = :idorder AND `order`.status != '0'");
    $statement->execute(['idorder' => $idorder]);
    $result = $statement->fetch();
    if (is_array($result)) {
      $details = $result;
    } else {
      return $this->handleRequest($response, 404);
    }

    return $this->handleRequest($response, 200, '', $details);
  }

  public function getDate(Request $request, Response $response, $args) {
    $date = $args['date'];
    if (empty($date))
      return $this->handleRequest($response, 400, 'Requerid date');

    $statement = $this->db->prepare("SELECT * FROM `order` INNER JOIN user ON `order`.user_iduser = user.iduser WHERE `order`.date_created = :date AND status != '0'");
    $statement->execute(['date' => $date]);
    $result = $statement->fetch();
    if (is_array($result)) {
      $details = $result;
    } else {
      return $this->handleRequest($response, 404);
    }

    return $this->handleRequest($response, 200, '', $details);
  }

  public function getStatus(Request $request, Response $response, $args) {
    $status = $args['status'];
    if (empty($status))
      return $this->handleRequest($response, 400, 'Requerid status');

    if ($status === 'Todo') {
      return $this->getAllOrders($response);
    }

    $statement = $this->db->prepare("SELECT * FROM `order` INNER JOIN user ON `order`.user_iduser = user.iduser WHERE status = :status AND `order`.active != '0'");
    $statement->execute(['status' => $status]);
    $result = $statement->fetchAll();
    if (is_array($result)) {
      $details = $result;
    } else {
      return $this->handleRequest($response, 404);
    }

    return $this->handleRequest($response, 200, '', $details);
  }

  public function getActive(Request $request, Response $response, $args) {
    $active = $args['active'];
    if (empty($active))
      return $this->handleRequest($response, 400, 'Requerid active');

    if ($active === 0) {
      $statement = $this->db->prepare("SELECT * FROM `order` INNER JOIN user ON `order`.user_iduser = user.iduser");
    } else {
      $statement = $this->db->prepare("SELECT * FROM `order` INNER JOIN user ON `order` . user_iduser = user . iduser WHERE `order` . active = :active");
    }
    $statement->execute(['active' => $active]);
    $result = $statement->fetchAll();
    if (is_array($result)) {
      $details['count'] = $statement->rowCount();
    } else {
      return $this->handleRequest($response, 404);
    }

    return $this->handleRequest($response, 200, '', $details);
  }

  public function getAll(Request $request, Response $response, $args) {
    return $this->getAllOrders($response);
  }

  public function register(Request $request, Response $response, $args) {
    $request_body            = $request->getParsedBody();
    $origin_order            = $request_body['origin_order'];
    $destination_order       = $request_body['destination_order'];
    $maximum_delivery_date   = $request_body['maximum_delivery_date'];
    $maximum_withdrawal_date = $request_body['maximum_withdrawal_date'];
    $message                 = $request_body['message'];
    $price                   = $request_body['price'];
    $user_iduser             = $request_body['user_iduser'];

    if (!isset($origin_order) && !isset($destination_order) && !isset($maximum_delivery_date) && !isset($maximum_withdrawal_date) && !isset($message) && !isset($price) && !isset($user_iduser)) {
      return $this->handleRequest($response, 400, 'Invalid request. Required origin_order destination_order maximum_delivery_date maximum_withdrawal_date message price user_iduser');
    }
    $prepare = $this->db->prepare(
      "INSERT INTO `order` (`origin_order`, `destination_order`, `maximum_delivery_date`, `maximum_withdrawal_date`, `message`, `price`, `date_created`, `user_iduser`) 
                    VALUES(:origin_order, :destination_order, :maximum_delivery_date, :maximum_withdrawal_date, :message, :price, NOW(), :user_iduser)"
    );
    $result  = $prepare->execute([
                                   'origin_order'            => $origin_order,
                                   'destination_order'       => $destination_order,
                                   'maximum_delivery_date'   => $maximum_delivery_date,
                                   'maximum_withdrawal_date' => $maximum_withdrawal_date,
                                   'message'                 => $message,
                                   'price'                   => $price,
                                   'user_iduser'             => $user_iduser,
                                 ]);

    return $result ? $this->handleRequest($response, 200, "Datos registrados", ['idOrder' => $this->db->lastInsertId()]) : $this->handleRequest($response, 500);
  }

  public function update(Request $request, Response $response, $args) {
    $request_body            = $request->getParsedBody();
    $idorder                 = $request_body['idorder'];
    $origin_order            = $request_body['origin_order'];
    $destination_order       = $request_body['destination_order'];
    $maximum_delivery_date   = $request_body['maximum_delivery_date'];
    $maximum_withdrawal_date = $request_body['maximum_withdrawal_date'];
    $message                 = $request_body['message'];
    $price                   = $request_body['price'];

    if (!isset($idorder) && !isset($origin_order) && !isset($destination_order) && !isset($maximum_delivery_date) && !isset($maximum_withdrawal_date) && !isset($message) && !isset($price)) {
      return $this->handleRequest($response, 400, 'Invalid request. Required idorder origin_order destination_order maximum_delivery_date maximum_withdrawal_date message price');
    }

    $prepare = $this->db->prepare(
      "UPDATE `order` 
      SET origin_order = :origin_order, destination_order = :destination_order,
          maximum_delivery_date = :maximum_delivery_date, maximum_withdrawal_date = :maximum_withdrawal_date,
          message = :message, price = :price
      WHERE idorder = :idorder"
    );

    $result = $prepare->execute([
                                  'idorder'                 => $idorder,
                                  'origin_order'            => $origin_order,
                                  'destination_order'       => $destination_order,
                                  'maximum_delivery_date'   => $maximum_delivery_date,
                                  'maximum_withdrawal_date' => $maximum_withdrawal_date,
                                  'message'                 => $message,
                                  'price'                   => $price,
                                ]);
    return $result ? $this->handleRequest($response, 201, "Datos actualizados") : $this->handleRequest($response, 500);
  }

  public function updateStatus(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $idorder      = $request_body['idorder'];
    $status       = $request_body['status'];

    if (!isset($idorder) && !isset($status)) {
      return $this->handleRequest($response, 400, 'Invalid request. Required idorder status');
    }

    if ($status === 'Cancelado') {
      $message        = $request_body['message'];
      $user_iduser    = $request_body['user_iduser'];
      $emailRecipient = $request_body['emailRecipient'];
      $emailSender    = $request_body['emailSender'];

      $prepare = $this->db->prepare("UPDATE `order` SET status = :status, active = 0 WHERE idorder = :idorder");
      $prepare->execute(['idorder' => $idorder, 'status' => $status]);

      if (!isset($emailRecipient) && !isset($emailSender) && !isset($message) && !isset($user_iduser)) {
        return $this->handleRequest($response, 400, 'Invalid request. Required emailRecipient emailSender message user_iduser');
      }

      $statement = $this->db->prepare("SELECT payment . status, payment . idpayment FROM payment INNER JOIN `order` o on payment . order_idorder = o . idorder WHERE order_idorder = :idorder AND payment . active != 0");
      $statement->execute(['idorder' => $idorder]);
      $payment = $statement->fetch();

      if (is_array($payment) && $payment['status'] === 'Activo') {
        $prepare = $this->db->prepare("UPDATE payment SET status = :status WHERE idpayment = :idpayment");
        $result  = $prepare->execute(['idpayment' => $payment['idpayment'], 'status' => 'Cancelado']);

        if ($result) {
          $message = $message . ' <br>.El comprobante de pago es: ' . $result['image'];
          $this->sendEmail('Orden cancelada', $message, $request_body['emailRecipient'], $request_body['emailSender']);
          return $this->handleRequest($response, 200, "Datos actualizados y pago cancelado", ['isPayment' => true]);
        } else {
          return $this->handleRequest($response, 500);
        }
      }
      $prepare = $this->db->prepare(
        "INSERT INTO message_notification(`message`, `user_iduser`, `date_created`) VALUES(:message, :user_iduser, NOW())"
      );
      $prepare->execute(['message' => $message, 'user_iduser' => $user_iduser]);

      return $this->handleRequest($response, 200, "Datos actualizados", ['isPayment' => false]);
    } else {
      $prepare = $this->db->prepare("UPDATE `order` SET status = :status WHERE idorder = :idorder");
      $result  = $prepare->execute(['idorder' => $idorder, 'status' => $status]);

      return $result ? $this->handleRequest($response, 201, "Datos actualizados") : $this->handleRequest($response, 500);
    }
  }

  public function updateActive(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $idorder      = $request_body['idorder'];
    $active       = $request_body['active'];

    if (!isset($idorder) && !isset($active)) {
      return $this->handleRequest($response, 400, 'Invalid request. Required idorder active');
    }

    $prepare = $this->db->prepare(
      "UPDATE `order` SET active = :active WHERE idorder = :idorder"
    );

    $result = $prepare->execute([
                                  'idorder' => $idorder,
                                  'active'  => $active,
                                ]);
    return $result ? $this->handleRequest($response, 201, "Datos actualizados") : $this->handleRequest($response, 500);
  }

  public function delete(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $idorder      = $request_body['idorder'];

    if (!isset($idorder)) {
      return $this->handleRequest($response, 400, 'Missing fields idorder');
    }

    $statement = $this->db->prepare("SELECT * FROM `order` WHERE idorder = :idorder AND status != '0'");
    $statement->execute(['idorder' => $idorder]);
    $result = $statement->fetch();
    if (is_array($result)) {
      $prepare = $this->db->prepare(
        "UPDATE `order` SET status = :active WHERE idorder = :idorder"
      );
      $result  = $prepare->execute(['idorder' => $idorder, 'active' => 0]);
      return $result ? $this->handleRequest($response, 201, "Datos eliminados") : $this->handleRequest($response, 500);
    } else {
      return $this->handleRequest($response, 404, "id not found");
    }
  }

  /**
   * @param Response $response
   * @return array
   */
  public function getAllOrders(Response $response) {
    $statement = $this->db->prepare("SELECT * FROM `order` INNER JOIN user ON `order` . user_iduser = user . iduser WHERE status != '0'");
    $statement->execute();
    $result  = $statement->fetchAll();
    $details = is_array($result) ? $result : [];

    return $this->handleRequest($response, 200, '', $details);
  }

}
