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

  public function getAll(Request $request, Response $response, $args) {
    $order    = $request->getQueryParam('order', $default = 'ASC');
    $limit    = $request->getQueryParam('limit', $default = '-1');
    $id       = $request->getQueryParam('id', $default = false);
    $category = $request->getQueryParam('category', $default = false);
    $favorite = $request->getQueryParam('favorite', $default = false);
    $shopped  = $request->getQueryParam('shopped', $default = false);

    if ($favorite === true) {
      switch ($order) {
        case 'ASC':
          $statement = $this->db->prepare("SELECT * FROM product INNER JOIN product_review pr on product.id = pr.product_id
                                        WHERE product.id = :id AND product.active != '0' 
                                        ORDER BY pr.stars ASC LIMIT 5");
          $statement->execute(['id' => $id, 'limit' => $limit]);
          break;

        case 'DESC':
          $statement = $this->db->prepare("SELECT * FROM product  
                                        WHERE product.id = :id AND product.active != '0'
                                        ORDER BY product.inserted_at ASC");
          $statement->execute(['id' => $id, 'limit' => $limit]);
          break;

        case 'RAND':
          $statement = $this->db->prepare("SELECT * FROM product  
                                        WHERE product.id = :id AND product.active != '0'
                                        ORDER BY product.inserted_at ASC");
          $statement->execute(['id' => $id, 'limit' => $limit]);
          break;
      }
    }

    if ($category === true) {
      switch ($order) {
        case 'ASC':
          $statement = $this->db->prepare("SELECT * FROM product INNER JOIN product_review pr on product.id = pr.product_id
                                        WHERE product.id = :id AND product.active != '0' 
                                        ORDER BY pr.stars ASC LIMIT 5");
          $statement->execute(['id' => $id, 'limit' => $limit]);
          break;

        case 'DESC':
          $statement = $this->db->prepare("SELECT * FROM product  
                                        WHERE product.id = :id AND product.active != '0'
                                        ORDER BY product.inserted_at ASC");
          $statement->execute(['id' => $id, 'limit' => $limit]);
          break;

        case 'RAND':
          $statement = $this->db->prepare("SELECT * FROM product  
                                        WHERE product.id = :id AND product.active != '0'
                                        ORDER BY product.inserted_at ASC");
          $statement->execute(['id' => $id, 'limit' => $limit]);
          break;
      }
    }

    if ($shopped === true) {
      switch ($order) {
        case 'ASC':
          $statement = $this->db->prepare("SELECT * FROM product INNER JOIN product_review pr on product.id = pr.product_id
                                        WHERE product.id = :id AND product.active != '0' 
                                        ORDER BY pr.stars ASC LIMIT 5");
          $statement->execute(['id' => $id, 'limit' => $limit]);
          break;

        case 'DESC':
          $statement = $this->db->prepare("SELECT * FROM product  
                                        WHERE product.id = :id AND product.active != '0'
                                        ORDER BY product.inserted_at ASC");
          $statement->execute(['id' => $id, 'limit' => $limit]);
          break;

        case 'RAND':
          $statement = $this->db->prepare("SELECT * FROM product  
                                        WHERE product.id = :id AND product.active != '0'
                                        ORDER BY product.inserted_at ASC");
          $statement->execute(['id' => $id, 'limit' => $limit]);
          break;
      }
    }

    if ($id) {
      $statement = $this->db->prepare("SELECT * FROM product  
                                        WHERE product.id = :id AND product.active != '0' ORDER BY :order");
      $statement->execute(['id' => $id, 'order' => $order]);
    } else {
      $statement = $this->db->prepare("SELECT * FROM product 
                                        WHERE product.active != '0' ORDER BY product.inserted_at DESC");
      $statement->execute(['order' => $order]);
    }

    $result = $statement->fetchAll();

    if (is_array($result)) {
      foreach ($result as $index => $product) {
        $result = $this->getImagesProducts($product, $result, $index);
        $result = $this->getCategoriesProducts($product, $result, $index);
      }
      return $this->handleRequest($response, 200, '', $result);
    } else {
      return $this->handleRequest($response, 204, '', []);
    }
  }

  public function register(Request $request, Response $response, $args) {
    $request_body      = $request->getParsedBody();
    $sku               = $request_body['sku'];
    $name              = $request_body['name'];
    $description_short = $request_body['description_short'];
    $description_one   = $request_body['description_one'];
    $description_two   = $request_body['description_two'];
    $preparation       = $request_body['preparation'];
    $regular_price     = $request_body['regular_price'];
    $quantity          = $request_body['quantity'];
    $user_id           = $request_body['user_id'];

    if (!isset($sku) && !isset($name) && !isset($description_short) && !isset($description_one) && !isset($preparation)
      && !isset($description_two) && !isset($regular_price) && !isset($quantity) && !isset($user_id) && !isset($category_id)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    if ($this->existProduct($name)) {
      $prepare = $this->db->prepare(
        "INSERT INTO product (sku, name, description_short, description_one, description_two, preparation, regular_price, quantity, user_id) 
        VALUES (:sku, :name,  :description_short,  :description_one,  :description_two, :regular_price, :regular_price, :quantity, :user_id)"
      );
      $result  = $prepare->execute([
                                     'sku'               => $sku,
                                     'name'              => $name,
                                     'description_short' => $description_short,
                                     'description_one'   => $description_one,
                                     'description_two'   => $description_two,
                                     'preparation'       => $preparation,
                                     'regular_price'     => $regular_price,
                                     'quantity'          => $quantity,
                                     'user_id'           => $user_id,
                                   ]);
    } else {
      return $this->handleRequest($response, 400, 'Nombre ya registrado');
    }

    return $this->postSendResponse($response, $result, 'Datos registrados');
  }

  public function update(Request $request, Response $response, $args) {
    $request_body      = $request->getParsedBody();
    $id                = $request_body['id'];
    $sku               = $request_body['name_object'];
    $name              = $request_body['name'];
    $description_short = $request_body['description_short'];
    $description_one   = $request_body['description_one'];
    $description_two   = $request_body['description_two'];
    $preparation       = $request_body['preparation'];
    $regular_price     = $request_body['regular_price'];
    $quantity          = $request_body['quantity'];
    $user_id           = $request_body['user_id'];

    if (!isset($sku) && !isset($name) && !isset($description_short) && !isset($description_one)
      && !isset($description_two) && !isset($preparation) && !isset($regular_price) && !isset($quantity) && !isset($user_id)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    $prepare = $this->db->prepare(
      "UPDATE product 
        SET sku = :sku, name = :name, description_short = :description_short, description_one = :description_one, 
            description_two = :description_two, preparation = :preparation, regular_price = :regular_price, 
            quantity = :quantity, user_id = :user_id
        WHERE id = :id"
    );

    $result = $prepare->execute([
                                  'id'                => $id,
                                  'sku'               => $sku,
                                  'name'              => $name,
                                  'description_short' => $description_short,
                                  'description_one'   => $description_one,
                                  'description_two'   => $description_two,
                                  'preparation'       => $preparation,
                                  'regular_price'     => $regular_price,
                                  'quantity'          => $quantity,
                                  'user_id'           => $user_id,
                                ]);

    return $this->postSendResponse($response, $result, 'Datos actualizados');
  }

  public function delete(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $id           = $request_body['id'];

    if (!isset($id)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    $statement = $this->db->prepare("SELECT * FROM product WHERE id = :id AND active != '0'");
    $statement->execute(['id' => $id]);
    $result = $statement->fetch();
    if (is_array($result)) {
      $prepare = $this->db->prepare("UPDATE product SET active = :active WHERE id = :id");
      $result  = $prepare->execute(['id' => $id, 'active' => 0]);
      return $this->postSendResponse($response, $result, 'Datos eliminados');
    } else {
      return $this->handleRequest($response, 404, "id not found");
    }
  }

  /**
   * @param $name
   * @return mixed
   */
  public function existProduct($name) {
    $statement = $this->db->prepare("SELECT name FROM product WHERE name = :name");
    $statement->execute(['name' => $name]);
    return empty($statement->fetchAll());
  }

  /**
   * @param       $product
   * @param array $result
   * @param       $index
   * @return array
   */
  public function getImagesProducts($product, array $result, $index) {
    $statement = $this->db->prepare("SELECT product_image.id AS id_image, image FROM product_image
                                        INNER JOIN product p on product_image.product_id = p.id
                                        WHERE product_image.active != 0 AND product_image.product_id = :id");
    $statement->execute(['id' => $product['id']]);
    $resultImage = $statement->fetchAll();

    if (is_array($resultImage) and !empty($resultImage)) {
      $result[$index]['images'] = $resultImage;
    } else {
      $result[$index]['images'] = [['id_image' => 0, 'image' => 'http://goa-backend/src/uploads/no-image.png']];
    }
    return $result;
  }

  /**
   * @param $product
   * @param $result
   * @param $index
   * @return mixed
   */
  public function getCategoriesProducts($product, $result, $index) {
    $statement = $this->db->prepare("SELECT category.id, category.name FROM category
                                        INNER JOIN product_category pc on category.id = pc.category_id
                                        WHERE category.active != 0 AND pc.product_id = :id");
    $statement->execute(['id' => $product['id']]);
    $resultCategory = $statement->fetchAll();

    if (is_array($resultCategory) and !empty($resultCategory)) {
      $result[$index]['categories'] = $resultCategory;
    }
    return $result;
  }

}
