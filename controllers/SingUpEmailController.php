<?php

namespace App\Controller;

use Psr\Container\ContainerInterface as ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SingUpEmailController extends HandleRequest {

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
      $statement = $this->db->prepare("SELECT * FROM sing_up_email WHERE id = :id ORDER BY " . $order);
      $statement->execute(['id' => $id]);
    } else {
      $statement = $this->db->prepare("SELECT * FROM sing_up_email");
      $statement->execute();
    }
    return $this->getSendResponse($response, $statement);
  }

  public function register(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();

    $message = '';
    $valid   = true;
    $email   = $email = $request_body['email'];

    if (empty($email)) {
      $message = "The email address field must not be blank";
      $valid   = false;
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $message = "You must fill the field with a valid email address";
      $valid   = false;
    }

    if ($valid) {
      if ($this->alreadyEmail($email)) {
        return $this->handleRequest($response, 409, "Email already exist");
      } else {
        $prepare = $this->db->prepare("INSERT INTO sing_up_email (email) VALUES (:email)");
        $result = $prepare->execute(['email' => $email,]);
      }
      $this->sendEmail('Subscribed to Gardens of America', 'Welcome to Gardens of America', $email);
      return $this->postSendResponse($response, $result, 'Send email');
    } else {
      return $this->handleRequest($response, 400, $message);
    }
  }

  /**
   * @param $email
   * @return bool
   */
  public function alreadyEmail($email) {
    $existingSignUp = $this->db->prepare("SELECT COUNT(*) FROM sing_up_email WHERE email = ':email'");
    $existingSignUp->execute(['email' => $email]);
    $data_exists = ($existingSignUp->fetchColumn() > 0) ? true : false;
    return $data_exists;
  }

}
