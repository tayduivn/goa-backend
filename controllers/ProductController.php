<?php

namespace App\Controller;

use Psr\Container\ContainerInterface as ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProductController extends HandleRequest {

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
    $idobject = (int)$args['id'];
    if (empty($idobject))
      return $this->handleRequest($response, 400, 'Requerid id');

    $statement = $this->db->prepare("SELECT * FROM object WHERE idobject = :idobject AND active != '0'");
    $statement->execute(['idobject' => $idobject]);;
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

    $statement = $this->db->prepare("SELECT * FROM object WHERE date_created = :date AND active != '0'");
    $statement->execute(['date' => $date]);;
    $result = $statement->fetch();
    if (is_array($result)) {
      $details = $result;
    } else {
      return $this->handleRequest($response, 404);
    }

    return $this->handleRequest($response, 200, '', $details);
  }

  public function getAll(Request $request, Response $response, $args) {
    $statement = $this->db->prepare("SELECT * FROM object WHERE active != '0'");
    $statement->execute();
    $result  = $statement->fetchAll();
    $details = is_array($result) ? $result : [];

    return $this->handleRequest($response, 200, '', $details);
  }

  public function register(Request $request, Response $response, $args) {
    $request_body      = $request->getParsedBody();
    $name              = $request_body['name_object'];
    $height            = $request_body['height'];
    $width             = $request_body['width'];
    $weight            = $request_body['weight'];
    $quantity          = $request_body['quantity'];
    $service_idservice = $request_body['service_idservice'];

    $uploadedFiles = $request->getUploadedFiles();

    $uploadedFile = $uploadedFiles['image'];
    if (isset($uploadedFile) && $uploadedFile !== null && $uploadedFile->getError() === UPLOAD_ERR_OK) {
      $filename = $this->moveUploadedFile($this->upload, $uploadedFile);
      $response->write('uploaded ' . $filename . '<br/>');
    } else {
      $filename = 'no-image.png';
    }

    if (!isset($name) && !isset($height) && !isset($width) && !isset($weight) && !isset($quantity)) {
      return $this->handleRequest($response, 400, 'Invalid request. Required name_object, height, width, weight and quantity');
    }
    $prepare = $this->db->prepare(
      "INSERT INTO object (name_object, image, height, width, weight, quantity, date_created, service_idservice) 
        VALUES (:name, :image, :height,  :width,  :weight,  :quantity, NOW(), :service_idservice)"
    );
    $result  = $prepare->execute([
                                   'name'              => $name,
                                   'height'            => $height,
                                   'width'             => $width,
                                   'weight'            => $weight,
                                   'quantity'          => $quantity,
                                   'image'             => $this->getBaseURL() . "/src/uploads/" . $filename,
                                   'service_idservice' => $service_idservice
                                 ]);

    return $result ? $this->handleRequest($response, 200, "Datos registrados", ['idObject' => $this->db->lastInsertId()]) : $this->handleRequest($response, 500);
  }

  public function update(Request $request, Response $response, $args) {
    $request_body      = $request->getParsedBody();
    $idobject          = $request_body['idobject'];
    $name              = $request_body['name_object'];
    $height            = $request_body['height'];
    $width             = $request_body['width'];
    $weight            = $request_body['weight'];
    $quantity          = $request_body['quantity'];
    $service_idservice = $request_body['service_idservice'];

    $uploadedFiles = $request->getUploadedFiles();

    $uploadedFile = $uploadedFiles['image'];
    if (isset($uploadedFile) && $uploadedFile !== null && $uploadedFile->getError() === UPLOAD_ERR_OK) {
      $filename = $this->moveUploadedFile($this->upload, $uploadedFile);
      $response->write('uploaded ' . $filename . '<br/>');
    } else {
      $filename = 'no-image.png';
    }

    if (!isset($name) && !isset($height) && !isset($width) && !isset($weight) && !isset($quantity)) {
      return $this->handleRequest($response, 400, 'Invalid request. Required name_object, height, width, weight and quantity');
    }

    $prepare = $this->db->prepare(
      "UPDATE object SET name_object = :name, image = :image, height = :height, width = :width, weight = :weight, quantity = :quantity
        WHERE idobject = :idobject"
    );

    $result = $prepare->execute([
                                  'idobject'          => $idobject,
                                  'name'              => $name,
                                  'height'            => $height,
                                  'width'             => $width,
                                  'weight'            => $weight,
                                  'quantity'          => $quantity,
                                  'image'             => $this->getBaseURL() . "/src/uploads/" . $filename,
                                  'service_idservice' => $service_idservice
                                ]);
    return $result ? $this->handleRequest($response, 201, "Datos actualizados") : $this->handleRequest($response, 500);
  }

  public function delete(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $idobject     = $request_body['idobject'];

    if (!isset($idobject)) {
      return $this->handleRequest($response, 400, 'Missing fields idobject');
    }

    $statement = $this->db->prepare("SELECT * FROM object WHERE idobject = :idobject AND active != '0'");
    $statement->execute(['idobject' => $idobject]);
    $result = $statement->fetch();
    if (is_array($result)) {
      $prepare = $this->db->prepare(
        "UPDATE object SET active = :active WHERE idobject = :idobject"
      );
      $result  = $prepare->execute(['idobject' => $idobject, 'active' => 0]);
      return $result ? $this->handleRequest($response, 201, "Datos eliminados") : $this->handleRequest($response, 500);
    } else {
      return $this->handleRequest($response, 404, "id not found");
    }
  }

}
