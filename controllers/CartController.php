<?php

namespace App\Controller;

use Psr\Container\ContainerInterface as ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CartController extends HandleRequest {

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
    $id         = $request->getQueryParam('id');
    $showByUser = $request->getQueryParam('showByUser', $default = false);
    $userId     = $request->getQueryParam('userId');
    $status     = $request->getQueryParam('status', $default = false);

    $all = isset($id) || isset($userId) || $showByUser ? false : true;

    if (isset($id)) {
      $query     = "SELECT cart.id AS cart_id, cart.status, cart.active, cart.inserted_at, cart.updated_at, cart.user_id, 
                    u.id, u.email, u.first_name, u.last_name, u.password, u.address, u.phone, u.active, u.role_id, 
                    u.inserted_at, u.updated_at
                    FROM cart INNER JOIN user u on cart.user_id = u.id 
                    WHERE cart.id = :id AND cart.active != '0' ORDER BY cart.inserted_at ASC";
      $statement = $this->db->prepare($query);
      $statement->execute(['id' => $id]);
    }

    if (isset($userId)) {
      if ($status === 'current') {
        $query     = "SELECT cart.id AS cart_id, cart.status, cart.active, cart.inserted_at, cart.updated_at, cart.user_id, 
                    u.id, u.email, u.first_name, u.last_name, u.password, u.address, u.phone, u.active, u.role_id, 
                    u.inserted_at, u.updated_at
                    FROM cart INNER JOIN user u on cart.user_id = u.id
                    WHERE cart.user_id = :user_id AND cart.active != 0 AND cart.status = :statusCart";
        $statement = $this->db->prepare($query);
        $statement->execute(['user_id' => $userId, 'statusCart' => $status]);
      } else {
        $query     = "SELECT cart.id AS cart_id, cart.status, cart.active, cart.inserted_at, cart.updated_at, cart.user_id, 
                    u.id, u.email, u.first_name, u.last_name, u.password, u.address, u.phone, u.active, u.role_id, 
                    u.inserted_at, u.updated_at
                    FROM cart INNER JOIN user u on cart.user_id = u.id
                    WHERE cart.user_id = :user_id AND cart.active != 0";
        $statement = $this->db->prepare($query);
        $statement->execute(['user_id' => $userId]);
      }
    }

    if ($showByUser) {
      $query     = "SELECT cart.id AS cart_id, cart.status, cart.active, cart.inserted_at, cart.updated_at, cart.user_id, 
                    u.id, u.email, u.first_name, u.last_name, u.password, u.address, u.phone, u.active, u.role_id, 
                    u.inserted_at, u.updated_at
                    FROM cart INNER JOIN user u on cart.user_id = u.id
                    WHERE cart.active != '0' GROUP BY cart.user_id";
      $statement = $this->db->prepare($query);
      $statement->execute();
    }

    if ($all) {
      $query     = "SELECT cart.id AS cart_id, cart.status, cart.active, cart.inserted_at, cart.updated_at, cart.user_id, 
                    u.id, u.email, u.first_name, u.last_name, u.password, u.address, u.phone, u.active, u.role_id, 
                    u.inserted_at, u.updated_at
                    FROM cart INNER JOIN user u on cart.user_id = u.id
                    WHERE cart.active != '0'";
      $statement = $this->db->prepare($query);
      $statement->execute();
    }

    $result = $statement->fetchAll();

    if (is_array($result)) {
      foreach ($result as $indexCart => $cart) {
        $result = $this->getCartsProducts($this->db, $cart['cart_id'], $result, $indexCart);
        if (isset($result[$indexCart]['products']) AND is_array($result[$indexCart]['products'])) {
          $products = $result[$indexCart]['products'];
          foreach ($products as $index => $product) {
            $products = $this->getImagesProducts($this->db, $product, $products, $index);
            $products = $this->getCategoriesProducts($this->db, $product, $products, $index);
            $products = $this->getReviewsProducts($this->db, $product, $products, $index);
          }
          $result[$indexCart]['products'] = $products;
        } else {
          $result[$indexCart]['products'] = [];
        }
      }
      return $this->handleRequest($response, 200, '', $result);
    } else {
      return $this->handleRequest($response, 204, '', []);
    }
  }

  public function register(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $user_id      = $request_body['user_id'];

    if (!isset($user_id)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }
    $prepare = $this->db->prepare("INSERT INTO cart (`user_id`) VALUES (:user_id)");
    $result  = $prepare->execute(['user_id' => $user_id]);

    return $this->postSendResponse($response, $result, 'Datos registrados');
  }

  public function update(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $id           = $request_body['id'];
    $status       = $request_body['status'];

    if (!isset($id) && !isset($status)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    $prepare = $this->db->prepare("UPDATE cart SET status = :status WHERE id = :id");
    $result  = $prepare->execute(['id' => $id, 'status' => $status,]);

    return $this->postSendResponse($response, $result, 'Datos actualizados');
  }

  public function delete(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $id           = $request_body['id'];

    if (!isset($id)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    $statement = $this->db->prepare("SELECT * FROM cart WHERE id = :id AND active != '0'");
    $statement->execute(['id' => $id]);
    $result = $statement->fetch();
    if (is_array($result)) {
      $prepare = $this->db->prepare("UPDATE cart SET active = :active WHERE id = :id");
      $result  = $prepare->execute(['id' => $id, 'active' => 0]);
      return $this->postSendResponse($response, $result, 'Datos eliminados');
    } else {
      return $this->handleRequest($response, 404, "Información no encontrada");
    }
  }

}
