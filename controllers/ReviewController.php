<?php

namespace App\Controller;

use Psr\Container\ContainerInterface as ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ReviewController extends HandleRequest {

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
    $idimages = (int)$args['id'];
    if (empty($idimages))
      return $this->handleRequest($response, 400, 'Requerid id');

    $statement = $this->db->prepare("SELECT * FROM images WHERE idimages = :idimages AND active != '0'");
    $statement->execute(['idimages' => $idimages]);
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

    $statement = $this->db->prepare("SELECT * FROM images WHERE date_created = :date AND active != '0'");
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
    $statement = $this->db->prepare("SELECT * FROM images WHERE active != '0'");
    $statement->execute();
    $result  = $statement->fetchAll();
    $details = is_array($result) ? $result : [];

    return $this->handleRequest($response, 200, '', $details);
  }

  public function register(Request $request, Response $response, $args) {
    $uploadedFiles = $request->getUploadedFiles();

    $uploadedFile = $uploadedFiles['image'];
    if (isset($uploadedFile) && $uploadedFile !== null && $uploadedFile->getError() === UPLOAD_ERR_OK) {
      $filename = $this->moveUploadedFile($this->upload, $uploadedFile);
    } else {
      return $this->handleRequest($response, 400, 'No upload image');
    }

    $prepare = $this->db->prepare(
      "INSERT INTO images (`image`, `date_created`) VALUES (:image, NOW())"
    );
    $result  = $prepare->execute([
                                   'image' => $this->getBaseURL() . "/src/uploads/" . $filename
                                 ]);
    return $result ? $this->handleRequest($response, 201, "Datos registrados") : $this->handleRequest($response, 500);
  }

  public function update(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $idcategory   = $request_body['idcategory'];

    $uploadedFiles = $request->getUploadedFiles();

    $uploadedFile = $uploadedFiles['image'];
    if (isset($uploadedFile) && $uploadedFile !== null && $uploadedFile->getError() === UPLOAD_ERR_OK) {
      $filename = $this->moveUploadedFile($this->upload, $uploadedFile);
      $response->write('uploaded ' . $filename . '<br/>');
    } else {
      return $this->handleRequest($response, 400, 'No upload image');
    }

    $prepare = $this->db->prepare(
      "UPDATE category SET image = :image WHERE idcategory = :idcategory"
    );

    $result = $prepare->execute([
                                  'idcategory' => $idcategory,
                                  'image'      => $this->getBaseURL() . "/src/uploads/" . $filename,
                                ]);
    return $result ? $this->handleRequest($response, 201, "Datos actualizados") : $this->handleRequest($response, 500);
  }

  public function delete(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $idimages    = $request_body['idimages'];

    if (!isset($idimages)) {
      return $this->handleRequest($response, 400, 'Missing fields idimages');
    }

    $statement = $this->db->prepare("SELECT * FROM images WHERE $idimages = :idimages AND active != '0'");
    $statement->execute(['idimages' => $idimages]);
    $result = $statement->fetch();
    if (is_array($result)) {
      $prepare = $this->db->prepare(
        "UPDATE images SET active = :active WHERE idimages = :idimages"
      );
      $result = $prepare->execute(['idimages' => $idimages, 'active' => 0]);
      return $result ? $this->handleRequest($response, 201, "Datos eliminados") : $this->handleRequest($response, 500);
    } else {
      return $this->handleRequest($response, 404, "id not found");
    }
  }

}
