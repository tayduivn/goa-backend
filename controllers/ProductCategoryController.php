<?php

namespace App\Controller;

use Psr\Container\ContainerInterface as ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProductCategoryController extends HandleRequest {

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

  public function register(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $product_id   = $request_body['product_id'];
    $category_id  = $request_body['category_id'];

    if (!isset($product_id) && !isset($category_id)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    $query   = "INSERT INTO product_category (`product_id`, `category_id`) VALUES (:product_id, :category_id)";
    $prepare = $this->db->prepare($query);
    $result  = $prepare->execute(['product_id' => $product_id, 'category_id' => $category_id,]);

    return $this->postSendResponse($response, $result, 'Datos registrados');
  }

  public function update(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $idcategory   = $request_body['id'];
    $product_id   = $request_body['product_id'];
    $category_id  = $request_body['category_id'];


    if (!isset($idcategory) && !isset($product_id) && !isset($category_id)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    $str     = "UPDATE product_category SET product_id = :product_id, category_id = :category_id WHERE id = :idcategory";
    $prepare = $this->db->prepare($str);
    $result  = $prepare->execute(['idcategory' => $idcategory, 'product_id' => $product_id, 'category_id' => $category_id,]);

    return $this->postSendResponse($response, $result, 'Datos actualizados');
  }

  public function delete(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $product_id   = $request_body['product_id'];
    $category_id  = $request_body['category_id'];

    if (!isset($product_id) && !isset($category_id)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    if (!$this->checkProductCategory($product_id, $category_id)) {
      $query   = "DELETE FROM product_category WHERE category_id = :category_id AND product_id = :product_id";
      $prepare = $this->db->prepare($query);
      $result  = $prepare->execute(['product_id' => $product_id, 'category_id' => $category_id,]);

      return $this->postSendResponse($response, $result, 'Datos eliminados');
    } else {
      return $this->handleRequest($response, 404, "Categoría no existe");
    }
  }

  /**
   * @param $product_id
   * @param $category_id
   * @return mixed
   */
  public function checkProductCategory($product_id, $category_id) {
    $query     = "SELECT * FROM product_category WHERE product_id = :product_id AND category_id = :category_id AND active != '0'";
    $statement = $this->db->prepare($query);
    $statement->execute(['product_id' => $product_id, 'category_id' => $category_id,]);
    return empty($statement->fetchAll());
  }

}
