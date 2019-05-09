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
    $order        = $request->getQueryParam('order', $default = 'DESC');
    $limit        = $request->getQueryParam('limit', $default = '12');
    $page         = $request->getQueryParam('page', $page = '1');
    $id           = $request->getQueryParam('id', $default = false);
    $favorite     = $request->getQueryParam('favorite', $default = false);
    $new          = $request->getQueryParam('new', $default = false);
    $shopped      = $request->getQueryParam('shopped', $default = false);
    $category     = $request->getQueryParam('category', $category = false);
    $categoryName = $request->getQueryParam('categoryName');

    $skip     = ($page - 1) * $limit;
    $lastPage = 0;
    $count    = 0;

    $all = $new || $favorite || $shopped || $id || $categoryName ? false : true;

    if ($favorite) {
      switch ($order) {
        case 'ASC':
          $query     = "SELECT product.id, product.sku, product.name, product.description_short, product.description_one, 
                        product.description_two, product.preparation, product.nutrition, product.quantity, product.active, 
                        product.inserted_at, product.updated_at, product.user_id, product.regular_price, 
                        pr.id AS review_id, pr.title, pr.stars, pr.active, pr.inserted_at, pr.updated_at, pr.user_id, 
                        pr.product_id, pr.message 
                        FROM product INNER JOIN product_review pr on product.id = pr.product_id
                        WHERE product.active != '0' AND pr.active != 0
                        ORDER BY pr.stars ASC LIMIT " . $limit;
          $statement = $this->db->prepare($query);
          $statement->execute();
          break;

        case 'RAND':
          $query     = "SELECT product.id, product.sku, product.name, product.description_short, product.description_one, 
                        product.description_two, product.preparation, product.nutrition, product.quantity, product.active, 
                        product.inserted_at, product.updated_at, product.user_id, product.regular_price, 
                        pr.id AS review_id, pr.title, pr.stars, pr.active, pr.inserted_at, pr.updated_at, pr.user_id, 
                        pr.product_id, pr.message 
                        FROM product INNER JOIN product_review pr on product.id = pr.product_id
                        WHERE product.active != '0' AND pr.active != 0
                        ORDER BY RAND() LIMIT " . $limit;
          $statement = $this->db->prepare($query);
          $statement->execute();
          break;

        default:
          $query     = "SELECT product.id, product.sku, product.name, product.description_short, product.description_one, 
                        product.description_two, product.preparation, product.nutrition, product.quantity, product.active, 
                        product.inserted_at, product.updated_at, product.user_id, product.regular_price, 
                        pr.id AS review_id, pr.title, pr.stars, pr.active, pr.inserted_at, pr.updated_at, pr.user_id, 
                        pr.product_id, pr.message 
                        FROM product INNER JOIN product_review pr on product.id = pr.product_id
                        WHERE product.active != '0' AND pr.active != 0
                        ORDER BY pr.stars DESC LIMIT " . $limit;
          $statement = $this->db->prepare($query);
          $statement->execute();
          break;
      }
    }

    if ($categoryName) {
      $categoryName = explode(",", $categoryName);
      $in           = str_repeat('?,', count($categoryName) - 1) . '?';

      switch ($order) {
        case 'ASC':
          $query     = "SELECT product.id, product.sku, product.name, product.description_short, 
                        product.description_one, product.description_two, product.preparation, 
                        product.regular_price, product.nutrition, product.quantity, product.active, 
                        product.inserted_at, product.updated_at, product.user_id, 
                        pc.id AS product_category_id, pc.active, pc.inserted_at, pc.updated_at, pc.category_id, pc.product_id, 
                        FROM product INNER JOIN product_category pc on product.id = pc.product_id
                        INNER JOIN category c on pc.category_id = c.id
                        WHERE c.name IN ($in)
                        ORDER BY product.inserted_at ASC LIMIT " . $limit;
          $statement = $this->db->prepare($query);
          break;

        case 'RAND':
          $query     = "SELECT product.id, product.sku, product.name, product.description_short, 
                        product.description_one, product.description_two, product.preparation, 
                        product.regular_price, product.nutrition, product.quantity, product.active, 
                        product.inserted_at, product.updated_at, product.user_id, 
                        pc.id AS product_category_id, pc.active, pc.inserted_at, pc.updated_at, pc.category_id, pc.product_id, 
                        FROM product INNER JOIN product_category pc on product.id = pc.product_id
                        INNER JOIN category c on pc.category_id = c.id
                        WHERE c.name IN ($in)
                        ORDER BY RAND() LIMIT " . $limit;
          $statement = $this->db->prepare($query);
          break;

        default:
          $query     = "SELECT product.id, product.sku, product.name, product.description_short, 
                        product.description_one, product.description_two, product.preparation, 
                        product.regular_price, product.nutrition, product.quantity, product.active, 
                        product.inserted_at, product.updated_at, product.user_id, 
                        pc.id AS product_category_id, pc.active, pc.inserted_at, pc.updated_at, pc.category_id, pc.product_id, 
                        FROM product INNER JOIN product_category pc on product.id = pc.product_id
                        INNER JOIN category c on pc.category_id = c.id
                        WHERE c.name IN ($in)
                        ORDER BY product.inserted_at DESC LIMIT " . $limit;
          $statement = $this->db->prepare($query);
          break;
      }
      $statement->execute($categoryName);
    }

    if ($new) {
      switch ($order) {
        case 'ASC':
          $query     = "SELECT * FROM product  
                        WHERE product.active != '0'
                        ORDER BY product.inserted_at ASC LIMIT " . $limit;
          $statement = $this->db->prepare($query);
          $statement->execute();
          break;

        case 'RAND':
          $query     = "SELECT * FROM product  
                        WHERE product.active != '0'
                        ORDER BY RAND() LIMIT " . $limit;
          $statement = $this->db->prepare($query);
          $statement->execute();
          break;

        default:
          $query     = "SELECT * FROM product  
                        WHERE product.active != '0'
                        ORDER BY product.inserted_at DESC LIMIT " . $limit;
          $statement = $this->db->prepare($query);
          $statement->execute();
          break;
      }
    }

    if ($category && $id !== false) {
      $query     = "SELECT c.id AS category_id
                    FROM product INNER JOIN product_category pc on product.id = pc.product_id 
                    INNER JOIN category c on pc.category_id = c.id
                    WHERE product.id = :id AND product.active != '0'";
      $statement = $this->db->prepare($query);
      $statement->execute(['id' => $id]);
      $result = $statement->fetchAll();

      if (is_array($result)) {
        $myCategories = '';
        foreach ($result as $index => $item) {
          if (!next($result)) {
            $myCategories = $myCategories . $result[$index]['category_id'];
          } else {
            $myCategories = $myCategories . $result[$index]['category_id'] . ',';
          }
        }

        switch ($order) {
          case 'ASC':
            $query     = "SELECT product.id, product.sku, product.name, product.description_short, product.description_one, 
                          product.description_two, product.preparation, product.regular_price, product.nutrition, product.quantity, 
                          product.active, product.inserted_at, product.updated_at, product.user_id, 
                          pc.id AS category_id, pc.active, pc.inserted_at, pc.updated_at, pc.category_id, pc.product_id 
                          FROM product INNER JOIN product_category pc on product.id = pc.product_id
                          WHERE product.active != 0 AND pc.active != 0 AND pc.category_id IN ( " . $myCategories . ") 
                          AND product.id != :id GROUP BY product.id
                          ORDER BY product.inserted_at ASC LIMIT " . $limit;
            $statement = $this->db->prepare($query);
            $statement->execute(['id' => $id]);
            break;

          case 'RAND':
            $query     = "SELECT product.id, product.sku, product.name, product.description_short, product.description_one, 
                          product.description_two, product.preparation, product.regular_price, product.nutrition, product.quantity, 
                          product.active, product.inserted_at, product.updated_at, product.user_id, 
                          pc.id AS category_id, pc.active, pc.inserted_at, pc.updated_at, pc.category_id, pc.product_id
                          FROM product INNER JOIN product_category pc on product.id = pc.product_id
                          WHERE product.active != 0 AND pc.active != 0 AND pc.category_id IN ( " . $myCategories . ") 
                          AND product.id != :id GROUP BY product.id
                          ORDER BY RAND() LIMIT " . $limit;
            $statement = $this->db->prepare($query);
            $statement->execute(['id' => $id]);
            break;

          default:
            $query     = "SELECT product.id, product.sku, product.name, product.description_short, product.description_one, 
                          product.description_two, product.preparation, product.regular_price, product.nutrition, product.quantity, 
                          product.active, product.inserted_at, product.updated_at, product.user_id, 
                          pc.id AS category_id, pc.active, pc.inserted_at, pc.updated_at, pc.category_id, pc.product_id 
                          FROM product INNER JOIN product_category pc on product.id = pc.product_id
                          WHERE product.active != 0 AND pc.active != 0 AND pc.category_id IN ( " . $myCategories . ") 
                          AND product.id != :id GROUP BY product.id
                          ORDER BY product.inserted_at DESC LIMIT " . $limit;
            $statement = $this->db->prepare($query);
            $statement->execute(['id' => $id]);
            break;
        }
      } else {
        return $this->handleRequest($response, 400, 'Id product incorrect');
      }
    }

    if ($shopped) {
      switch ($order) {
        case 'ASC':
          $query     = "SELECT product.id, product.sku, product.name, product.description_short, 
                        product.description_one, product.description_two, product.preparation, 
                        product.regular_price, product.nutrition, product.quantity, product.active, 
                        product.inserted_at, product.updated_at, product.user_id, 
                        pr.id AS review_id, pr.title, pr.message, pr.stars, pr.active, pr.inserted_at, pr.updated_at, 
                        pr.user_id, pr.product_id 
                        FROM product INNER JOIN product_review pr on product.id = pr.product_id
                        WHERE product.active != '0'
                        ORDER BY product.inserted_at ASC LIMIT " . $limit;
          $statement = $this->db->prepare($query);
          $statement->execute();
          break;

        case 'RAND':
          $query     = "SELECT product.id, product.sku, product.name, product.description_short, 
                        product.description_one, product.description_two, product.preparation, 
                        product.regular_price, product.nutrition, product.quantity, product.active, 
                        product.inserted_at, product.updated_at, product.user_id, 
                        pr.id AS review_id, pr.title, pr.message, pr.stars, pr.active, pr.inserted_at, pr.updated_at, 
                        pr.user_id, pr.product_id 
                        FROM product INNER JOIN product_review pr on product.id = pr.product_id
                        WHERE product.active != '0' 
                        ORDER BY RAND() LIMIT " . $limit;
          $statement = $this->db->prepare($query);
          $statement->execute();
          break;

        default:
          $query     = "SELECT product.id, product.sku, product.name, product.description_short, 
                        product.description_one, product.description_two, product.preparation, 
                        product.regular_price, product.nutrition, product.quantity, product.active, 
                        product.inserted_at, product.updated_at, product.user_id, 
                        pr.id AS review_id, pr.title, pr.message, pr.stars, pr.active, pr.inserted_at, pr.updated_at, 
                        pr.user_id, pr.product_id 
                        FROM product INNER JOIN product_review pr on product.id = pr.product_id
                        WHERE product.active != '0' 
                        ORDER BY pr.stars DESC LIMIT " . $limit;
          $statement = $this->db->prepare($query);
          $statement->execute();
          break;
      }
    }

    if ($id AND !$category) {
      switch ($order) {
        case 'ASC':
          $query     = "SELECT product.id, product.sku, product.name, product.description_short, product.description_one, 
                        product.description_two, product.preparation, product.nutrition, product.quantity, product.active, 
                        product.inserted_at, product.updated_at, product.user_id, product.regular_price
                        FROM product
                        WHERE product.id = :id AND product.active != '0' 
                        ORDER BY product.id ASC LIMIT " . $limit;
          $statement = $this->db->prepare($query);
          $statement->execute(['id' => $id]);
          break;

        case 'RAND':
          $query     = "SELECT product.id, product.sku, product.name, product.description_short, product.description_one, 
                        product.description_two, product.preparation, product.nutrition, product.quantity, product.active, 
                        product.inserted_at, product.updated_at, product.user_id, product.regular_price
                        FROM product
                        WHERE product.id = :id AND product.active != '0' 
                        ORDER BY RAND() LIMIT " . $limit;
          $statement = $this->db->prepare($query);
          $statement->execute(['id' => $id]);
          break;

        default:
          $query     = "SELECT product.id, product.sku, product.name, product.description_short, product.description_one, 
                        product.description_two, product.preparation, product.nutrition, product.quantity, product.active, 
                        product.inserted_at, product.updated_at, product.user_id, product.regular_price
                        FROM product
                        WHERE product.id = :id AND product.active != '0' 
                        ORDER BY product.id DESC LIMIT " . $limit;
          $statement = $this->db->prepare($query);
          $statement->execute(['id' => $id]);
          break;
      }
    }

    if ($all) {
      switch ($order) {
        case 'ASC':
          $statement = $this->db->prepare("SELECT count(product.id) FROM product WHERE product.active != '0'");
          $statement->execute();
          $result = $statement->fetchAll();

          $count = $result[0]["count(product.id)"];

          $query     = $this->db->prepare("SELECT * FROM product 
                                        WHERE product.active != '0' ORDER BY product.inserted_at ASC LIMIT " . $limit);
          $statement = $this->db->prepare($query);
          $statement->execute(['limit' => $limit, 'skip' => $skip]);
          $lastPage = (ceil($count / $limit) == 0 ? 1 : ceil($count / $limit));
          break;

        case 'RAND':
          $statement = $this->db->prepare("SELECT count(product.id) FROM product WHERE product.active != '0'");
          $statement->execute();
          $result = $statement->fetchAll();

          $count = $result[0]["count(product.id)"];

          $query     = $this->db->prepare("SELECT * FROM product 
                                        WHERE product.active != '0' ORDER BY RAND() LIMIT " . $limit);
          $statement = $this->db->prepare($query);
          $statement->execute(['limit' => $limit, 'skip' => $skip]);
          $lastPage = (ceil($count / $limit) == 0 ? 1 : ceil($count / $limit));
          break;

        default:
          $statement = $this->db->prepare("SELECT count(product.id) FROM product WHERE product.active != '0'");
          $statement->execute();
          $result = $statement->fetchAll();

          $count = $result[0]["count(product.id)"];

          $query     = "SELECT * FROM product 
                        WHERE product.active != '0' ORDER BY product.inserted_at DESC LIMIT " . $skip . "," . $limit;
          $statement = $this->db->prepare($query);
          $statement->execute(['limit' => $limit, 'skip' => $skip]);
          $lastPage = (ceil($count / $limit) == 0 ? 1 : ceil($count / $limit));
          break;
      }
    }

    $result = $statement->fetchAll();

    if (is_array($result)) {
      foreach ($result as $index => $product) {
        $result = $this->getImagesProducts($this->db, $product, $result, $index);
        $result = $this->getCategoriesProducts($this->db, $product, $result, $index);
        $result = $this->getReviewsProducts($this->db, $product, $result, $index);
      }
      $pagination = ['count' => (int)$count, 'limit' => (int)$limit, 'lastPage' => $lastPage, 'page' => (int)$page];
      return $this->handleRequest($response, 200, '', $result, $pagination);
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
      $query   = "INSERT INTO product (sku, name, description_short, description_one, description_two, preparation, regular_price, quantity, user_id) 
        VALUES (:sku, :name,  :description_short,  :description_one,  :description_two, :regular_price, :regular_price, :quantity, :user_id)";
      $prepare = $this->db->prepare($query);
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
