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
    $order    = $request->getQueryParam('order', $default = 'DESC');
    $limit    = $request->getQueryParam('limit', $default = '20'); /* TODO: how to infinite limit */
    $id       = $request->getQueryParam('id', $default = false);
    $favorite = $request->getQueryParam('favorite', $default = false);
    $new      = $request->getQueryParam('new', $default = false);
    $shopped  = $request->getQueryParam('shopped', $default = false);
    $category = $request->getQueryParam('category', $category = false);

    $all = $new || $favorite || $shopped || $id ? false : true;

    if ($favorite) {
      switch ($order) {
        case 'ASC':
          $statement = $this->db->prepare("SELECT product.id, product.sku, product.name, product.description_short, product.description_one, 
                                          product.description_two, product.preparation, product.nutrition, product.quantity, product.active, 
                                          product.inserted_at, product.updated_at, product.user_id, product.regular_price, 
                                          pr.id AS review_id, pr.title, pr.stars, pr.active, pr.inserted_at, pr.updated_at, pr.user_id, 
                                          pr.product_id, pr.message 
                                          FROM product INNER JOIN product_review pr on product.id = pr.product_id
                                          WHERE product.active != '0' AND pr.active != 0
                                          ORDER BY pr.stars ASC LIMIT " . $limit);
          $statement->execute();
          break;

        case 'RAND':
          $statement = $this->db->prepare("SELECT product.id, product.sku, product.name, product.description_short, product.description_one, 
                                          product.description_two, product.preparation, product.nutrition, product.quantity, product.active, 
                                          product.inserted_at, product.updated_at, product.user_id, product.regular_price, 
                                          pr.id AS review_id, pr.title, pr.stars, pr.active, pr.inserted_at, pr.updated_at, pr.user_id, 
                                          pr.product_id, pr.message 
                                          FROM product INNER JOIN product_review pr on product.id = pr.product_id
                                          WHERE product.active != '0' AND pr.active != 0
                                          ORDER BY pr.stars ASC LIMIT " . $limit);
          $statement->execute();
          break;

        default:
          $statement = $this->db->prepare("SELECT product.id, product.sku, product.name, product.description_short, product.description_one, 
                                          product.description_two, product.preparation, product.nutrition, product.quantity, product.active, 
                                          product.inserted_at, product.updated_at, product.user_id, product.regular_price, 
                                          pr.id AS review_id, pr.title, pr.stars, pr.active, pr.inserted_at, pr.updated_at, pr.user_id, 
                                          pr.product_id, pr.message 
                                          FROM product INNER JOIN product_review pr on product.id = pr.product_id
                                          WHERE product.active != '0' AND pr.active != 0
                                          ORDER BY pr.stars DESC LIMIT " . $limit);
          $statement->execute();
          break;
      }
    }

    if ($new) {
      switch ($order) {
        case 'ASC':
          $statement = $this->db->prepare("SELECT * FROM product  
                                        WHERE product.active != '0'
                                        ORDER BY product.inserted_at ASC LIMIT " . $limit);
          $statement->execute();
          break;

        case 'RAND':
          $statement = $this->db->prepare("SELECT * FROM product  
                                        WHERE product.active != '0'
                                        ORDER BY product.inserted_at ASC LIMIT " . $limit);
          $statement->execute();
          break;

        default:
          $statement = $this->db->prepare("SELECT * FROM product  
                                        WHERE product.active != '0'
                                        ORDER BY product.inserted_at DESC LIMIT " . $limit);
          $statement->execute();
          break;
      }
    }
    if ($category && $id !== false) {
      $statement = $this->db->prepare("SELECT c.id AS category_id
                                        FROM product INNER JOIN product_category pc on product.id = pc.product_id 
                                        INNER JOIN category c on pc.category_id = c.id
                                        WHERE product.id = :id AND product.active != '0'");
      $statement->execute(['id' => $id]);
      $result = $statement->fetchAll();

      if (is_array($result)) {
        $myCategories = array(1, 2);

        var_dump($myCategories);

        switch ($order) {
          case 'ASC':
            $statement = $this->db->prepare("SELECT * 
                                            FROM product INNER JOIN product_category pc on product.id = pc.product_id 
                                            INNER JOIN category c on pc.category_id = c.id
                                            WHERE product.active != '0' AND c.active != 0 AND c.id IN (" . $myCategories . ")
                                            ORDER BY product.inserted_at ASC LIMIT " . $limit);
            $statement->execute(['id' => $result->category_id]);
            break;

          case 'RAND':
            $statement = $this->db->prepare("SELECT * 
                                            FROM product INNER JOIN product_category pc on product.id = pc.product_id 
                                            INNER JOIN category c on pc.category_id = c.id
                                            WHERE product.active != '0' AND c.active != 0 AND c.id IN (" . $myCategories . ")
                                            ORDER BY product.inserted_at ASC LIMIT " . $limit);
            $statement->execute(['id' => $result->category_id]);
            break;

          default:
            $statement = $this->db->prepare("SELECT * 
                                            FROM product INNER JOIN product_category pc on product.id = pc.product_id 
                                            INNER JOIN category c on pc.category_id = c.id
                                            WHERE product.active != '0' AND c.active != 0 AND c.id IN (" . $myCategories . ")
                                            ORDER BY product.inserted_at DESC LIMIT " . $limit);
            $statement->execute();
            break;
        }
      } else {
        return $this->handleRequest($response, 400, 'Id product incorrect');
      }
    }

    if ($shopped) {
      switch ($order) {
        case 'ASC':
          $statement = $this->db->prepare("SELECT * FROM product  
                                        WHERE product.active != '0'
                                        ORDER BY product.inserted_at ASC LIMIT " . $limit);
          $statement->execute();
          break;

        case 'RAND':
          $statement = $this->db->prepare("SELECT * FROM product  
                                        WHERE product.active != '0'
                                        ORDER BY product.inserted_at ASC LIMIT " . $limit);
          $statement->execute();
          break;

        default:
          $statement = $this->db->prepare("SELECT * FROM product INNER JOIN product_review pr on product.id = pr.product_id
                                        WHERE product.active != '0' 
                                        ORDER BY pr.stars DESC LIMIT " . $limit);
          $statement->execute();
          break;
      }
    }

    if ($id) {
      switch ($order) {
        case 'ASC':
          $statement = $this->db->prepare("SELECT product.id, product.sku, product.name, product.description_short, product.description_one, 
                                          product.description_two, product.preparation, product.nutrition, product.quantity, product.active, 
                                          product.inserted_at, product.updated_at, product.user_id, product.regular_price, 
                                          pr.id AS review_id, pr.title, pr.stars, pr.active, pr.inserted_at, pr.updated_at, pr.user_id, 
                                          pr.product_id, pr.message 
                                          FROM product INNER JOIN product_review pr on product.id = pr.product_id
                                          INNER JOIN user u on pr.user_id = u.id
                                          WHERE product.id = :id AND product.active != '0' 
                                          ORDER BY product.id ASC LIMIT " . $limit);
          $statement->execute(['id' => $id]);
          break;

        case 'RAND':
          $statement = $this->db->prepare("SELECT product.id, product.sku, product.name, product.description_short, product.description_one, 
                                          product.description_two, product.preparation, product.nutrition, product.quantity, product.active, 
                                          product.inserted_at, product.updated_at, product.user_id, product.regular_price, 
                                          pr.id AS review_id, pr.title, pr.stars, pr.active, pr.inserted_at, pr.updated_at, pr.user_id, 
                                          pr.product_id, pr.message 
                                          FROM product INNER JOIN product_review pr on product.id = pr.product_id
                                          INNER JOIN user u on pr.user_id = u.id
                                          WHERE product.id = :id AND product.active != '0' 
                                          ORDER BY product.id DESC LIMIT " . $limit);
          $statement->execute(['id' => $id]);
          break;

        default:
          $statement = $this->db->prepare("SELECT product.id, product.sku, product.name, product.description_short, product.description_one, 
                                          product.description_two, product.preparation, product.nutrition, product.quantity, product.active, 
                                          product.inserted_at, product.updated_at, product.user_id, product.regular_price, 
                                          pr.id AS review_id, pr.title, pr.stars, pr.active, pr.inserted_at, pr.updated_at, pr.user_id, 
                                          pr.product_id, pr.message 
                                          FROM product INNER JOIN product_review pr on product.id = pr.product_id
                                          INNER JOIN user u on pr.user_id = u.id
                                          ORDER BY product.id DESC LIMIT " . $limit);
          $statement->execute(['id' => $id]);
          break;
      }
    }

    if ($all) {
      switch ($order) {
        case 'ASC':
          $statement = $this->db->prepare("SELECT * FROM product 
                                        WHERE product.active != '0' ORDER BY product.inserted_at ASC LIMIT " . $limit);
          $statement->execute();
          break;

        case 'RAND':
          $statement = $this->db->prepare("SELECT * FROM product 
                                        WHERE product.active != '0' ORDER BY product.inserted_at ASC LIMIT " . $limit);
          $statement->execute();
          break;

        default:
          $statement = $this->db->prepare("SELECT * FROM product 
                                        WHERE product.active != '0' ORDER BY product.inserted_at DESC LIMIT " . $limit);
          $statement->execute();
          break;
      }
    }

    $result = $statement->fetchAll();

    if (is_array($result)) {
      foreach ($result as $index => $product) {
        $result = $this->getImagesProducts($this->db, $product, $result, $index);
        $result = $this->getCategoriesProducts($this->db, $product, $result, $index);
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

}
