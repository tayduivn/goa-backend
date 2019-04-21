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
    $request_body       = $request->getParsedBody();
    $code               = $request_body['code'];
    $processor          = $request_body['processor'];
    $processor_trans_id = $request_body['processor_trans_id'];
    $cc_num             = $request_body['cc_num'];
    $cc_type            = $request_body['cc_type'];
    $order_id           = $request_body['order_id'];

    if (!isset($code) and !isset($processor) and !isset($processor_trans_id) and !isset($cc_num) and !isset($cc_type) and !isset($order_id)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    $prepare = $this->db->prepare(
      "INSERT INTO transaction (`code`, `processor`, `processor_trans_id`, `cc_num`, `cc_type`, `order_id`) 
      VALUES (:code, :processor, :processor_trans_id, :cc_num, :cc_type, :order_id)"
    );
    $result  = $prepare->execute([
                                   'code'               => $code,
                                   'processor'          => $processor,
                                   'processor_trans_id' => $processor_trans_id,
                                   'cc_num'             => $cc_num,
                                   'cc_type'            => $cc_type,
                                   'order_id'           => $order_id,
                                 ]);

    return $this->postSendResponse($response, $result, 'Datos registrados');
  }

  public function update(Request $request, Response $response, $args) {
    $request_body       = $request->getParsedBody();
    $id                 = $request_body['id'];
    $processor          = $request_body['processor'];
    $processor_trans_id = $request_body['processor_trans_id'];
    $cc_num             = $request_body['cc_num'];
    $cc_type            = $request_body['cc_type'];
    $order_id           = $request_body['order_id'];

    if (!isset($code) and !isset($processor) and !isset($processor_trans_id) and !isset($cc_num) and !isset($cc_type) and !isset($order_id)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    $prepare = $this->db->prepare(
      "UPDATE transaction 
        SET code = :code, processor = :processor, processor_trans_id = :processor_trans_id, cc_num = :cc_num, 
            cc_type = :cc_type, order_id = :order_id
      WHERE id = :id"
    );

    $result = $prepare->execute([
                                  'id'                 => $id,
                                  'code'               => $code,
                                  'processor'          => $processor,
                                  'processor_trans_id' => $processor_trans_id,
                                  'cc_num'             => $cc_num,
                                  'cc_type'            => $cc_type,
                                  'order_id'           => $order_id,
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

}
