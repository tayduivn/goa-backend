<?php

namespace App\Controller;

use Firebase\JWT\JWT;
use Psr\Container\ContainerInterface as ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController extends HandleRequest {

  private $db       = null;
  private $logger   = null;
  private $settings = null;
  private $session  = null;

  public function __construct(ContainerInterface $container) {
    $this->db       = $container->get('db');
    $this->logger   = $container->get('logger');
    $this->settings = $container->get('settings');
    $this->session  = $container->get('session');
  }

  public function getAll(Request $request, Response $response, $args) {
    $order = $request->getQueryParam('order', $default = 'ASC');
    $limit = $request->getQueryParam('limit', $default = '-1');
    $type  = $request->getQueryParam('type', $default = 'all');
    $id    = $request->getQueryParam('id', $default = false);

    if ($type !== 'all') {
      $statement = $this->db->prepare("SELECT user.id AS user_id, user.email, user.first_name, user.last_name, user.password, 
                                        user.address, user.phone, user.active, user.role_id, 
                                        user.inserted_at AS user_inserted_at, user.updated_at AS user_updated_at, 
                                        r.id, r.name, r.active, r.inserted_at, r.updated_at
                                        FROM user INNER JOIN role r on user.role_id = r.id 
                                        WHERE user.active != '0' AND r.name = :type");
      $statement->execute(['type' => $type]);
    } else if ($id) {
      $statement = $this->db->prepare("SELECT user.id AS user_id, user.email, user.first_name, user.last_name, user.password, 
                                        user.address, user.phone, user.active, user.role_id, 
                                        user.inserted_at, user.updated_at, r.id, r.name, r.active, r.inserted_at, r.updated_at
                                        FROM user INNER JOIN role r on user.role_id = r.id 
                                        WHERE user.active != '0' AND user.id = :id");
      $statement->execute(['id' => $id]);
    } else {
      $statement = $this->db->prepare("SELECT user.id AS user_id, user.email, user.first_name, user.last_name, user.password, 
                                        user.address, user.phone, user.active, user.role_id, 
                                        user.inserted_at, user.updated_at, r.id, r.name, r.active, r.inserted_at, r.updated_at
                                        FROM user INNER JOIN role r on user.role_id = r.id 
                                        WHERE user.active != '0'");
      $statement->execute();
    }

    $result  = $statement->fetchAll();
    $details = is_array($result) ? $result : [];

    return $this->handleRequest($response, 200, '', $details);
  }

  public function login(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $statement    = $this->db->prepare("SELECT user.id, user.email, user.first_name, user.last_name, user.password, 
                                        user.address, user.phone, user.active, user.role_id, 
                                        user.inserted_at, user.updated_at, 
                                        r.id AS role_id, r.name, r.active, r.inserted_at AS role_inserted, r.updated_at AS role_updated 
                                        FROM user INNER JOIN role r on user.role_id = r.id WHERE email= :email AND user.active != 0");
    $statement->bindParam("email", $request_body['email']);
    $statement->execute();
    $user = $statement->fetchObject();

    if (!$user) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    if (!password_verify($request_body['password'], $user->password)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    $token = JWT::encode(['id' => $user->id, 'email' => $user->email], $this->settings['jwt']['secret'], "HS256");

    return $this->handleRequest($response, 200, '', ['user' => $user, 'token' => $token]);
  }

  public function register(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $password     = $request_body['password'];
    $email        = $request_body['email'];
    $address      = isset($request_body['address']) ? $request_body['address'] : '';
    $phone        = isset($request_body['phone']) ? $request_body['phone'] : '';
    $role_id      = $request_body['role_id'];
    $first_name   = isset($request_body['first_name']) ? $request_body['first_name'] : '';
    $last_name    = isset($request_body['last_name']) ? $request_body['last_name'] : '';

    if (!isset($password) && !isset($email) && !isset($address) && !isset($phone) && !isset($role_id)) {
      return $this->handleRequest($response, 400, 'Faltan datos');
    }

    if ($this->validateUser($email)) {
      return $this->handleRequest($response, 409, "Email ya registrado");
    } else {
      $query   = "INSERT INTO user (`password`, `email`, `address`, `phone`, `role_id`, `first_name`, `last_name`) 
                  VALUES (:password, :email, :address, :phone, :role_id, :first_name, :last_name)";
      $prepare = $this->db->prepare($query);

      $result = $prepare->execute([
                                    'email'      => $email,
                                    'password'   => password_hash($password, PASSWORD_BCRYPT),
                                    'first_name' => $first_name,
                                    'last_name'  => $last_name,
                                    'address'    => $address,
                                    'phone'      => $phone,
                                    'role_id'    => $role_id,
                                  ]);

      if ($result) {
        return $this->handleRequest($response, 200, "Datos registrados", ['idUser' => $this->db->lastInsertId()]);
      } else {
        return $this->handleRequest($response, 500);
      }
    }
  }

  public function forgot(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $email        = $request_body['email'];

    if (!isset($email))
      return $this->handleRequest($response, 400, 'Email incorrect');

    $statement = $this->db->prepare("SELECT * FROM user WHERE email= :email");
    $statement->bindParam("email", $email);
    $statement->execute();
    $user = $statement->fetchObject();

    if (!$user)
      return $this->handleRequest($response, 400, 'This email not exist');

    $this->sendEmail('Recover password', 'Your password is: ', $args['email']);

    return $this->handleRequest($response, 201);
  }

  public function update(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $id           = $request_body['id'];
    $address      = isset($request_body['address']) ? $request_body['address'] : '';
    $phone        = isset($request_body['phone']) ? $request_body['phone'] : '';
    $role_id      = $request_body['role_id'];
    $first_name   = isset($request_body['first_name']) ? $request_body['first_name'] : '';
    $last_name    = isset($request_body['last_name']) ? $request_body['last_name'] : '';

    if (!isset($email) && !isset($address) && !isset($phone) && !isset($role_id)) {
      return $this->handleRequest($response, 400, 'Invalid request. Required iduser, street, phone, type');
    }

    $query   = "UPDATE user SET  first_name = :first_name, last_name = :last_name, address = :address, 
                phone = :phone, role_id = :role_id  WHERE id = :id";
    $prepare = $this->db->prepare($query);
    $result  = $prepare->execute([
                                   'id'         => $id,
                                   'first_name' => $first_name,
                                   'last_name'  => $last_name,
                                   'address'    => $address,
                                   'phone'      => $phone,
                                   'role_id'    => $role_id,
                                 ]);
    return $result ? $this->handleRequest($response, 201, "Datos actualizados") : $this->handleRequest($response, 500);
  }

  public function updatePassword(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $id           = $request_body['id'];
    $password     = $request_body['password'];
    $newPassword  = $request_body['newPassword'];
    $email        = $request_body['email'];

    $statement = $this->db->prepare("SELECT * FROM user WHERE email= :email");
    $statement->execute(["email" => $email]);
    $user = $statement->fetchObject();

    if (!$user) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    if (!password_verify($request_body['password'], $user->password)) {
      return $this->handleRequest($response, 400, 'Contraseña incorrecta');
    }

    if (!isset($id) && !isset($password)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    $prepare = $this->db->prepare("UPDATE user SET  password = :password WHERE id = :id");
    $result  = $prepare->execute([
                                   'id'       => $id,
                                   'password' => password_hash($newPassword, PASSWORD_BCRYPT),
                                 ]);
    return $result ? $this->handleRequest($response, 201, "Datos actualizados") : $this->handleRequest($response, 500);
  }

  public function delete(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $iduser       = $request_body['id'];

    if (!isset($iduser)) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    $statement = $this->db->prepare("SELECT * FROM user WHERE id = :iduser AND active != '0'");
    $statement->execute(['iduser' => $iduser]);
    $result = $statement->fetch();
    if (is_array($result)) {
      $prepare = $this->db->prepare(
        "UPDATE user SET active = :active WHERE id = :iduser"
      );
      $result  = $prepare->execute(['iduser' => $iduser, 'active' => 0]);
      return $this->postSendResponse($response, $result, 'Datos eliminados');
    } else {
      return $this->handleRequest($response, 404, "Información no encontrada");
    }
  }

  private function validateUser($email) {
    $statement = $this->db->prepare("SELECT count(*) FROM user WHERE email = :email");
    $result    = $statement->execute(['email' => $email]);
    return $result ? $statement->fetchColumn() : 0;
  }
}
