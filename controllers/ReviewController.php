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

  public function getAll(Request $request, Response $response, $args) {
    $id    = $request->getQueryParam('id');
    $order = $request->getQueryParam('order', $default = 'ASC');

    if ($id !== null) {
      $query     = "SELECT * 
                    FROM product_review INNER JOIN user u on product_review.user_id = u.id
                    INNER JOIN product p on product_review.product_id = p.id 
                    WHERE product_review.active != '0'";
      $statement = $this->db->prepare($query . $order);
      $statement->execute(['id' => $id]);
    } else {
      $query     = "SELECT product_review.id AS review_id, product_review.title, product_review.message, product_review.stars, 
                    product_review.active, product_review.inserted_at AS review_inserted_at, 
                    product_review.updated_at AS review_updated_at, product_review.user_id, product_review.product_id, 
                    u.id, u.email, u.first_name, u.last_name, u.password, u.address, u.phone, u.active, u.role_id, 
                    u.inserted_at, u.updated_at, 
                    p.id, p.sku, p.name, p.description_short, p.description_one, p.description_two, 
                    p.preparation, p.regular_price, p.quantity, p.active, p.inserted_at, p.updated_at, p.user_id 
                    FROM product_review INNER JOIN user u on product_review.user_id = u.id
                    INNER JOIN product p on product_review.product_id = p.id WHERE product_review.active != '0'";
      $statement = $this->db->prepare($query);
      $statement->execute();
    }
    return $this->getSendResponse($response, $statement);
  }

  public function register(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $stars        = (int)$request_body['stars'];
    $message      = $request_body['message'];
    $title        = $request_body['title'];
    $user_id      = $request_body['user_id'];
    $product_id   = $request_body['product_id'];

    if (!isset($message) and !isset($title) and !isset($user_id) and !isset($product_id) and !isset($stars)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    $query   = "INSERT INTO product_review (title, message, stars, user_id, product_id) 
                VALUES (:title, :message, :stars, :user_id, :product_id)";
    $prepare = $this->db->prepare($query);
    $result  = $prepare->execute([
                                   'title'      => $title,
                                   'message'    => $message,
                                   'stars'      => $stars,
                                   'user_id'    => $user_id,
                                   'product_id' => $product_id,
                                 ]);

    return $this->postSendResponse($response, $result, 'Datos registrados');
  }

  public function update(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $id           = $request_body['id'];
    $stars        = (int)$request_body['stars'];
    $message      = $request_body['message'];
    $title        = $request_body['title'];
    $user_id      = $request_body['user_id'];
    $product_id   = $request_body['product_id'];

    if (!isset($id) and !isset($stars) and !isset($message) and !isset($title) and !isset($user_id) and !isset($product_id)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    $query   = "UPDATE product_review 
                SET stars = :stars, message = :message, title = :title, user_id = :user_id, product_id = :product_id  WHERE id = :id";
    $prepare = $this->db->prepare($query);

    $result = $prepare->execute([
                                  'id'         => $id,
                                  'title'      => $title,
                                  'message'    => $message,
                                  'stars'      => $stars,
                                  'user_id'    => $user_id,
                                  'product_id' => $product_id,
                                ]);

    return $this->postSendResponse($response, $result, 'Datos actualizados');
  }

  public function delete(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $id           = $request_body['id'];

    if (!isset($id)) {
      return $this->handleRequest($response, 400, 'Missing fields id');
    }

    $statement = $this->db->prepare("SELECT * FROM product_review WHERE id = :id AND active != '0'");
    $statement->execute(['id' => $id]);
    $result = $statement->fetch();
    if (is_array($result)) {
      $prepare = $this->db->prepare("UPDATE product_review SET active = :active WHERE id = :id");
      $result  = $prepare->execute(['id' => $id, 'active' => 0]);
      return $this->postSendResponse($response, $result, 'Datos eliminados');
    } else {
      return $this->handleRequest($response, 404, "id not found");
    }
  }

}
