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

  public function getId(Request $request, Response $response, $args) {
    $idpayment = (int)$args['id'];
    if (empty($idpayment))
      return $this->handleRequest($response, 400, 'Requerid id');

    $statement = $this->db->prepare("SELECT * FROM payment WHERE order_idorder = :order_idorder AND active != '0'");
    $statement->execute(['order_idorder' => $idpayment]);
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

    $statement = $this->db->prepare("SELECT * FROM payment WHERE date_created = :date AND active != '0'");
    $statement->execute(['date' => $date]);
    $result = $statement->fetch();
    if (is_array($result)) {
      $details = $result;
    } else {
      return $this->handleRequest($response, 404);
    }

    return $this->handleRequest($response, 200, '', $details);
  }

  public function getAll(Request $request, Response $response, $args) {
    $statement = $this->db->prepare("SELECT * FROM payment WHERE active != '0'");
    $statement->execute();
    $result  = $statement->fetchAll();
    $details = is_array($result) ? $result : [];

    return $this->handleRequest($response, 200, '', $details);
  }

  public function register(Request $request, Response $response, $args) {
    $request_body  = $request->getParsedBody();
    $order_idorder = $request_body['order_idorder'];

    if (!isset($image) && !isset($order_idorder)) {
      return $this->handleRequest($response, 400, 'Invalid request. Required origin_order image order_idorder');
    }

    $uploadedFiles = $request->getUploadedFiles();

    $uploadedFile = $uploadedFiles['image'];
    if (isset($uploadedFile) && $uploadedFile !== null && $uploadedFile->getError() === UPLOAD_ERR_OK) {
      $filename = $this->moveUploadedFile($this->upload, $uploadedFile);
      $response->write('uploaded ' . $filename . '<br/>');
    } else {
      return $this->handleRequest($response, 400, 'No upload image');
    }

    $prepare = $this->db->prepare(
      "INSERT INTO payment (`image`, `date_created`, `order_idorder`) 
                    VALUES (:image, NOW(), :order_idorder)"
    );
    $result  = $prepare->execute([
                                   'image'         => $this->getBaseURL() . "/src/uploads/" . $filename,
                                   'order_idorder' => $order_idorder,
                                 ]);
    return $result ? $this->handleRequest($response, 201, "Datos registrados") : $this->handleRequest($response, 500);
  }

  public function update(Request $request, Response $response, $args) {
    $request_body  = $request->getParsedBody();
    $idpayment     = $request_body['idpayment'];
    $order_idorder = $request_body['order_idorder'];

    if (!isset($idpayment) && !isset($image) && !isset($order_idorder)) {
      return $this->handleRequest($response, 400, 'Invalid request. Required idpayment image order_idorder');
    }

    $uploadedFiles = $request->getUploadedFiles();

    $uploadedFile = $uploadedFiles['image'];
    if (isset($uploadedFile) && $uploadedFile !== null && $uploadedFile->getError() === UPLOAD_ERR_OK) {
      $filename = $this->moveUploadedFile($this->upload, $uploadedFile);
      $response->write('uploaded ' . $filename . '<br/>');
    } else {
      return $this->handleRequest($response, 400, 'No upload image');
    }

    $prepare = $this->db->prepare(
      "UPDATE payment SET image = :image, order_idorder = :order_idorder
      WHERE idpayment = :idpayment"
    );

    $result = $prepare->execute([
                                  'idpayment'     => $idpayment,
                                  'image'         => $this->getBaseURL() . "/src/uploads/" . $filename,
                                  'order_idorder' => $order_idorder
                                ]);
    return $result ? $this->handleRequest($response, 201, "Datos actualizados") : $this->handleRequest($response, 500);
  }

  public function delete(Request $request, Response $response, $args) {
    $request_body    = $request->getParsedBody();
    $idpayment = $request_body['idpayment'];

    if (!isset($idpayment)) {
      return $this->handleRequest($response, 400, 'Missing fields idpayment');
    }

    $statement = $this->db->prepare("SELECT * FROM payment WHERE idpayment = :idpayment AND active != '0'");
    $statement->execute(['idpayment' => $idpayment]);
    $result = $statement->fetch();
    if (is_array($result)) {
      $prepare = $this->db->prepare(
        "UPDATE payment SET active = :active WHERE idpayment = :idpayment"
      );
      $result  = $prepare->execute(['idpayment' => $idpayment, 'active' => 0]);
      return $result ? $this->handleRequest($response, 201, "Datos eliminados") : $this->handleRequest($response, 500);
    } else {
      return $this->handleRequest($response, 404, "id not found");
    }
  }

}
