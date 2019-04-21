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
    $iduser = $args['id'];

    $statement = $this->db->prepare("SELECT * FROM user WHERE active != '0' AND iduser != :iduser");
    $statement->execute(['iduser' => $iduser]);
    $result  = $statement->fetchAll();
    $details = is_array($result) ? $result : [];

    return $this->handleRequest($response, 200, '', $details);
  }

  public function profile(Request $request, Response $response, $args) {
    $iduser = $args['iduser'];

    if (empty($iduser))
      return $this->handleRequest($response, 400, 'Requerid iduser');

    $statement = $this->db->prepare("SELECT * FROM user WHERE iduser = :iduser AND active != '0'");
    $statement->execute(['iduser' => $iduser]);
    $result = $statement->fetch();
    if (is_array($result)) {
      $details = $result;
    } else {
      return $this->handleRequest($response, 404);
    }

    return $this->handleRequest($response, 200, '', $details);
  }

  public function getTypeUser(Request $request, Response $response, $args) {
    $type = $args['type'];

    if (empty($type))
      return $this->handleRequest($response, 400, 'Requerid type');

    $statement = $this->db->prepare("SELECT * FROM user WHERE type = :type AND active != '0'");
    $statement->execute(['type' => $type]);
    $result = $statement->fetchAll();
    if (is_array($result)) {
      $details = $result;
    } else {
      return $this->handleRequest($response, 404);
    }

    return $this->handleRequest($response, 200, '', $details);
  }

  public function login(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $statement    = $this->db->prepare("SELECT * FROM user LEFT JOIN transport t on user.iduser = t.user_iduser WHERE email= :email AND user.active != 0");
    $statement->bindParam("email", $request_body['email']);
    $statement->execute();
    $user = $statement->fetchObject();

    if (!$user) {
      $statement = $this->db->prepare("SELECT * FROM user LEFT JOIN transport t on user.iduser = t.user_iduser WHERE name= :email AND user.active != 0");
      $statement->bindParam("email", $request_body['email']);
      $statement->execute();
      $user = $statement->fetchObject();
      if (!$user) {
        return $this->handleRequest($response, 400, 'Datos incorrectos');
      }
    }

    if (!password_verify($request_body['password'], $user->password)) {
      return $this->handleRequest($response, 400, 'Contraseña incorrecta');
    }

    $token = JWT::encode(['id' => $user->iduser, 'email' => $user->email], $this->settings['jwt']['secret'], "HS256");

    return $this->handleRequest($response, 200, '', ['user' => $user, 'token' => $token]);
  }

  public function forgot(Request $request, Response $response, $args) {
    $statement = $this->db->prepare("SELECT * FROM user WHERE email= :email");
    $statement->bindParam("email", $args['email']);
    $statement->execute();
    $user = $statement->fetchObject();

    if (!$user) {
      return $this->handleRequest($response, 400, 'Este correo no existe');
    }

    $this->sendEmail('Recuperar clave', 'Su clave es: ', $args['email']);

    return $this->handleRequest($response, 201);
  }

  public function register(Request $request, Response $response, $args) {
    $request_body     = $request->getParsedBody();
    $name             = $request_body['name'];
    $password         = $request_body['password'];
    $email            = $request_body['email'];
    $street           = $request_body['street'];
    $phone            = $request_body['phone'];
    $type             = $request_body['type'];
    $person_name      = isset($request_body['person_name']) ? $request_body['person_name'] : '';
    $person_last_name = isset($request_body['person_last_name']) ? $request_body['person_last_name'] : '';

    if (!isset($name) && !isset($password) && !isset($email) && !isset($street) && !isset($phone) && !isset($type)) {
      return $this->handleRequest($response, 400, 'Invalid request. Required name, password, email, street, phone, type');
    }

    if ($this->validateUser($name, $email)) {
      return $this->handleRequest($response, 400, "Usuario o Email ya registrados");
    } else {
      $prepare = $this->db->prepare(
        "INSERT INTO user (`name`, `password`, `email`, `street`, `phone`, `type`, `person_name`, `person_last_name`, `date_created`) 
          VALUES (:name, :password, :email, :street, :phone, :type, :person_name, :person_last_name, NOW())"
      );

      $result = $prepare->execute([
                                    'name'             => $name,
                                    'password'         => password_hash($password, PASSWORD_BCRYPT),
                                    'email'            => $email,
                                    'street'           => $street,
                                    'phone'            => $phone,
                                    'type'             => $type,
                                    'person_name'      => $person_name,
                                    'person_last_name' => $person_last_name,
                                  ]);

      return $result ? $this->handleRequest($response, 200, "Datos registrados", ['idUser' => $this->db->lastInsertId()]) : $this->handleRequest($response, 500);
    }
  }

  public function update(Request $request, Response $response, $args) {
    $request_body     = $request->getParsedBody();
    $iduser           = $request_body['iduser'];
    $street           = $request_body['street'];
    $phone            = $request_body['phone'];
    $type             = $request_body['type'];
    $person_name      = isset($request_body['person_name']) ? $request_body['person_name'] : '';
    $person_last_name = isset($request_body['person_last_name']) ? $request_body['person_last_name'] : '';

    if (!isset($iduser) && !isset($street) && !isset($phone) && !isset($type)) {
      return $this->handleRequest($response, 400, 'Invalid request. Required iduser, street, phone, type');
    }

    $prepare = $this->db->prepare(
      "UPDATE user SET  street = :street, phone = :phone, type = :type, person_name = :person_name, person_last_name = :person_last_name 
        WHERE iduser = :iduser"
    );

    $result = $prepare->execute([
                                  'iduser'           => $iduser,
                                  'street'           => $street,
                                  'phone'            => $phone,
                                  'type'             => $type,
                                  'person_name'      => $person_name,
                                  'person_last_name' => $person_last_name,
                                ]);
    return $result ? $this->handleRequest($response, 201, "Datos actualizados") : $this->handleRequest($response, 500);
  }

  public function updatePassword(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $iduser       = $request_body['iduser'];
    $password     = $request_body['password'];
    $newPassword  = $request_body['newPassword'];
    $email        = $request_body['email'];

    $sql       = "SELECT * FROM user WHERE email= :email";
    $statement = $this->db->prepare($sql);
    $statement->bindParam("email", $email);
    $statement->execute();
    $user = $statement->fetchObject();

    if (!$user) {
      return $this->handleRequest($response, 400, 'Datos incorrectos');
    }

    if (!password_verify($request_body['password'], $user->password)) {
      return $this->handleRequest($response, 400, 'Contraseña incorrecta');
    }

    if (!isset($iduser) && !isset($password)) {
      return $this->handleRequest($response, 400, 'Invalid request. Required iduser, password');
    }

    $prepare = $this->db->prepare(
      "UPDATE user SET  password = :password WHERE iduser = :iduser"
    );

    $result = $prepare->execute([
                                  'iduser'   => $iduser,
                                  'password' => password_hash($newPassword, PASSWORD_BCRYPT),
                                ]);
    return $result ? $this->handleRequest($response, 201, "Datos actualizados") : $this->handleRequest($response, 500);
  }

  public function delete(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $iduser       = $request_body['iduser'];

    if (!isset($iduser)) {
      return $this->handleRequest($response, 400, 'Invalid request. Required iduser');
    }

    $statement = $this->db->prepare("SELECT * FROM user WHERE iduser = :iduser AND active != '0'");
    $statement->execute(['iduser' => $iduser]);
    $result = $statement->fetch();
    if (is_array($result)) {
      $prepare = $this->db->prepare(
        "UPDATE user SET active = :active WHERE iduser = :iduser"
      );
      $result  = $prepare->execute(['iduser' => $iduser, 'active' => 0]);
      return $result ? $this->handleRequest($response, 201, "Datos eliminados") : $this->handleRequest($response, 500);
    } else {
      return $this->handleRequest($response, 404, "id not found");
    }
  }

  private function validateUser($name, $email) {
    $statement = $this->db->prepare("SELECT count(*) FROM user WHERE name = :name OR email = :email");
    $result    = $statement->execute(['name' => $name, 'email' => $email]);
    return $result ? $statement->fetchColumn() : 0;
  }
}
