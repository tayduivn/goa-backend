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

  public function getId(Request $request, Response $response, $args) {
    $idcontent = (int)$args['id'];
    if (empty($idcontent))
      return $this->handleRequest($response, 400, 'Requerid id');

    $statement = $this->db->prepare("SELECT * FROM content WHERE idcontent = :idcontent AND active != '0'");
    $statement->execute(['idcontent' => $idcontent]);
    $result = $statement->fetch();
    if (is_array($result)) {
      $details = $result;
    } else {
      return $this->handleRequest($response, 404);
    }

    return $this->handleRequest($response, 200, '', $details);
  }

  public function getDate(Request $request, Response $response, $args) {
    $date = $args['date'];
    if (empty($date))
      return $this->handleRequest($response, 400, 'Requerid date');

    $statement = $this->db->prepare("SELECT * FROM content WHERE date_created = :date AND active != '0'");
    $statement->execute(['date' => $date]);
    $result = $statement->fetch();
    if (is_array($result)) {
      $details = $result;
    } else {
      return $this->handleRequest($response, 404);
    }

    return $this->handleRequest($response, 200, '', $details);
  }

  public function getStatus(Request $request, Response $response, $args) {
    $status = $args['status'];
    if (empty($status))
      return $this->handleRequest($response, 400, 'Requerid status');

    if ($status === 'Todo') {
      return $this->getAllContent($response);
    }

    $statement = $this->db->prepare("SELECT content.idcontent, content.date_created, content.active, content.object_idobject, content.order_idorder, 
                                            o.idorder, o.origin_order, o.destination_order, o.maximum_delivery_date, o.maximum_withdrawal_date, o.message, o.price, o.status, o.date_created, o.active AS active_order, o.user_iduser, 
                                            o2.idobject, o2.name_object, o2.image, o2.height, o2.width, o2.weight, o2.quantity, o2.date_created, o2.service_idservice, o2.active, 
                                            u.iduser, u.name, u.password, u.email, u.street, u.phone, u.person_name, u.person_last_name, u.date_created, u.type, u.active, 
                                            mo.idmessage_order, mo.date_withdrawal, mo.date_delivery, mo.id_client, mo.date_created, mo.active, mo.order_idorder, mo.user_iduser, mo.transport_idtransport, 
                                            t.idtransport, t.name_truck, t.active, t.user_iduser  
                                      FROM `content` 
                                      INNER JOIN `order` o on content.order_idorder = o.idorder 
                                      INNER JOIN object o2 on content.object_idobject = o2.idobject
                                      LEFT JOIN user u on o.user_iduser = u.iduser
                                      LEFT JOIN message_order mo on o.idorder = mo.order_idorder
                                      LEFT JOIN transport t on mo.transport_idtransport = t.idtransport
                                      WHERE o.status = :status AND o.active != 0 AND o2.active != 0");
    $statement->execute(['status' => $status]);
    $result = $statement->fetchAll();
    if (is_array($result)) {
      $details = $result;
    } else {
      return $this->handleRequest($response, 404);
    }

    return $this->handleRequest($response, 200, '', $details);
  }

  public function getAll(Request $request, Response $response, $args) {
    return $this->getAllContent($response);
  }

  public function register(Request $request, Response $response, $args) {
    $request_body    = $request->getParsedBody();
    $object_idobject = $request_body['object_idobject'];
    $order_idorder   = $request_body['order_idorder'];

    if (!isset($object_idobject) && !isset($order_idorder)) {
      return $this->handleRequest($response, 400, 'Invalid request. Required object_idobject order_idorder');
    }
    $prepare = $this->db->prepare(
      "INSERT INTO content (`object_idobject`, `order_idorder`, `date_created`) 
                    VALUES (:object_idobject, :order_idorder, NOW())"
    );
    $result  = $prepare->execute([
                                   'object_idobject' => $object_idobject,
                                   'order_idorder'   => $order_idorder,
                                 ]);
    return $result ? $this->handleRequest($response, 201, "Datos registrados") : $this->handleRequest($response, 500);
  }

  public function update(Request $request, Response $response, $args) {
    $request_body    = $request->getParsedBody();
    $idcontent       = $request_body['idcontent'];
    $object_idobject = $request_body['object_idobject'];
    $order_idorder   = $request_body['order_idorder'];

    if (!isset($idcontent) && !isset($object_idobject) && !isset($order_idorder)) {
      return $this->handleRequest($response, 400, 'Invalid request. Required idcontent object_idobject order_idorder');
    }

    $prepare = $this->db->prepare(
      "UPDATE content 
      SET object_idobject = :object_idobject, order_idorder = :order_idorder
      WHERE idcontent = :idcontent"
    );

    $result = $prepare->execute([
                                  'idcontent'       => $idcontent,
                                  'object_idobject' => $object_idobject,
                                  'order_idorder'   => $order_idorder
                                ]);
    return $result ? $this->handleRequest($response, 201, "Datos actualizados") : $this->handleRequest($response, 500);
  }

  public function delete(Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $idcontent    = $request_body['idcontent'];

    if (!isset($idcontent)) {
      return $this->handleRequest($response, 400, 'Missing fields idcontent');
    }

    $statement = $this->db->prepare("SELECT * FROM content WHERE idcontent = :idcontent AND active != '0'");
    $statement->execute(['idcontent' => $idcontent]);
    $result = $statement->fetch();
    if (is_array($result)) {
      $prepare = $this->db->prepare(
        "UPDATE content SET active = :active WHERE idcontent = :idcontent"
      );
      $result  = $prepare->execute(['idcontent' => $idcontent, 'active' => 0]);
      return $result ? $this->handleRequest($response, 201, "Datos eliminados") : $this->handleRequest($response, 500);
    } else {
      return $this->handleRequest($response, 404, "id not found");
    }
  }

  /**
   * @param Response $response
   * @return array
   */
  public function getAllContent(Response $response) {
    $statement = $this->db->prepare("SELECT content.idcontent, content.date_created, content.active, content.object_idobject, content.order_idorder, 
                                            o.idorder, o.origin_order, o.destination_order, o.maximum_delivery_date, o.maximum_withdrawal_date, o.message, o.price, o.status, o.date_created, o.active AS active_order, o.user_iduser, 
                                            o2.idobject, o2.name_object, o2.image, o2.height, o2.width, o2.weight, o2.quantity, o2.date_created, o2.service_idservice, o2.active, 
                                            u.iduser, u.name, u.password, u.email, u.street, u.phone, u.person_name, u.person_last_name, u.date_created, u.type, u.active, 
                                            mo.idmessage_order, mo.date_withdrawal, mo.date_delivery, mo.id_client, mo.date_created, mo.active, mo.order_idorder, mo.user_iduser, mo.transport_idtransport, 
                                            t.idtransport, t.name_truck, t.active, t.user_iduser 
                                      FROM `content` 
                                      INNER JOIN `order` o on content.order_idorder = o.idorder 
                                      INNER JOIN object o2 on content.object_idobject = o2.idobject
                                      LEFT JOIN user u on o.user_iduser = u.iduser
                                      LEFT JOIN message_order mo on o.idorder = mo.order_idorder
                                      LEFT JOIN transport t on mo.transport_idtransport = t.idtransport
                                      WHERE o.active != 0 AND o2.active != 0");
    $statement->execute();
    $result  = $statement->fetchAll();
    $details = is_array($result) ? $result : [];

    return $this->handleRequest($response, 200, '', $details);
  }

}
