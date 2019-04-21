<?php

namespace App\Controller;

use Psr\Container\ContainerInterface as ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CategoryController extends HandleRequest {

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
    $idcategory = (int)$args['id'];
    if (empty($idcategory))
      return $this->handleRequest($response, 400, 'Requerid id');

    $statement = $this->db->prepare("SELECT * FROM category WHERE idcategory = :idcategory AND active != '0'");
    $statement->execute(['idcategory' => $idcategory]);
    $result  = $statement->fetch();
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

    $statement = $this->db->prepare("SELECT * FROM category WHERE date_created = :date AND active != '0'");
    $statement->execute(['date' => $date]);
    $result  = $statement->fetch();
    if (is_array($result)) {
      $details = $result;
    } else {
      return $this->handleRequest($response, 404);
    }

    return $this->handleRequest($response, 200, '', $details);
  }

  public function getAll(Request $request, Response $response, $args) {
    $statement = $this->db->prepare("SELECT * FROM category WHERE active != '0'");
    $statement->execute();
    $result  = $statement->fetchAll();
    $details = is_array($result) ? $result : [];

    return $this->handleRequest($response, 200, '', $details);
  }

  public function register(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $name         = $request_body['name'];

    $uploadedFiles = $request->getUploadedFiles();

    $uploadedFile = $uploadedFiles['image'];
    if (isset($uploadedFile) && $uploadedFile !== null && $uploadedFile->getError() === UPLOAD_ERR_OK) {
      $filename = $this->moveUploadedFile($this->upload, $uploadedFile);
    } else {
      return $this->handleRequest($response, 400, 'No upload image');
    }

    if (!isset($name)) {
      return $this->handleRequest($response, 400, 'Invalid request. Required name');
    }
    $prepare = $this->db->prepare(
      "INSERT INTO category (`name`, `image`, `date_created`) VALUES (:name, :image, NOW())"
    );
    $result  = $prepare->execute([
                                   'name'  => $name,
                                   'image' => $this->getBaseURL() . "/src/uploads/" . $filename
                                 ]);
    return $result ? $this->handleRequest($response, 201, "Datos registrados") : $this->handleRequest($response, 500);
  }

  public function update(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $idcategory   = $request_body['idcategory'];
    $name         = $request_body['name'];

    $uploadedFiles = $request->getUploadedFiles();

    $uploadedFile = $uploadedFiles['image'];
    if (isset($uploadedFile) && $uploadedFile !== null && $uploadedFile->getError() === UPLOAD_ERR_OK) {
      $filename = $this->moveUploadedFile($this->upload, $uploadedFile);
      $response->write('uploaded ' . $filename . '<br/>');
    } else {
      return $this->handleRequest($response, 400, 'No upload image');
    }

    if (!isset($name)) {
      return $this->handleRequest($response, 400, 'Invalid request. Required name');
    }

    $prepare = $this->db->prepare(
      "UPDATE category SET name = :name, image = :image WHERE idcategory = :idcategory"
    );

    $result = $prepare->execute([
                                  'idcategory' => $idcategory,
                                  'name'       => $name,
                                  'image'      => $this->getBaseURL() . "/src/uploads/" . $filename,
                                ]);
    return $result ? $this->handleRequest($response, 201, "Datos actualizados") : $this->handleRequest($response, 500);
  }

  public function delete(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $idcategory    = $request_body['idcategory'];

    if (!isset($idcategory)) {
      return $this->handleRequest($response, 400, 'Missing fields idcategory');
    }

    $statement = $this->db->prepare("SELECT * FROM category WHERE $idcategory = :idcategory AND active != '0'");
    $statement->execute(['idcategory' => $idcategory]);
    $result = $statement->fetch();
    if (is_array($result)) {
      $prepare = $this->db->prepare(
        "UPDATE category SET active = :active WHERE idcategory = :idcategory"
      );
      $result = $prepare->execute(['idcategory' => $idcategory, 'active' => 0]);
      return $result ? $this->handleRequest($response, 201, "Datos eliminados") : $this->handleRequest($response, 500);
    } else {
      return $this->handleRequest($response, 404, "id not found");
    }
  }

}
