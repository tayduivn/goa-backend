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

  public function getAll(Request $request, Response $response, $args) {
    $id    = $request->getQueryParam('id');
    $order = $request->getQueryParam('order', $default = 'ASC');

    if ($id !== null) {
      $statement = $this->db->prepare("SELECT * FROM category WHERE id = :id AND active != '0' ORDER BY " . $order);
      $statement->execute(['id' => $id]);
    } else {
      $statement = $this->db->prepare("SELECT * FROM category WHERE active != '0'");
      $statement->execute();
    }
    return $this->getSendResponse($response, $statement);
  }

  public function register(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $name         = $request_body['name'];

    if (!isset($name)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    if ($this->checkCategoryName($name)) {
      $prepare = $this->db->prepare("INSERT INTO category (`name`) VALUES (:name)");
      $result  = $prepare->execute(['name' => $name,]);
    } else {
      return $this->handleRequest($response, 409, 'Ya se encuentra ese nombre');
    }

    return $this->postSendResponse($response, $result, 'Datos registrados');
  }

  public function update(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $idcategory   = $request_body['id'];
    $name         = $request_body['name'];

    if (!isset($name)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    if ($this->checkCategoryName($name)) {
      $prepare = $this->db->prepare("UPDATE category SET name = :name WHERE id = :idcategory");
      $result  = $prepare->execute(['idcategory' => $idcategory, 'name' => $name,]);
    } else {
      return $this->handleRequest($response, 409, 'Ya se encuentra ese nombre');
    }

    return $this->postSendResponse($response, $result, 'Datos actualizados');
  }

  public function delete(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $idcategory   = $request_body['id'];

    if (!isset($idcategory)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    $statement = $this->db->prepare("SELECT * FROM category WHERE id = :idcategory AND active != '0'");
    $statement->execute(['idcategory' => $idcategory]);
    $result = $statement->fetch();
    if (is_array($result)) {
      $prepare = $this->db->prepare("UPDATE category SET active = :active WHERE id = :idcategory");
      $result  = $prepare->execute(['idcategory' => $idcategory, 'active' => 0]);

      return $this->postSendResponse($response, $result, 'Datos eliminados');
    } else {
      return $this->handleRequest($response, 404, "Categoría no existe");
    }
  }

  /**
   * @param $name
   * @return mixed
   */
  public function checkCategoryName($name) {
    $statement = $this->db->prepare("SELECT name FROM category WHERE name = :name AND id != 0");
    $statement->execute(['name' => $name]);
    return empty($statement->fetchAll());
  }

}
