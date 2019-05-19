<?php

namespace App\Controller;

use Psr\Container\ContainerInterface as ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PaymentController extends HandleRequest {

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
    $statement = $this->db->prepare("SELECT * FROM payment");
    $statement->execute();
    return $this->getSendResponse($response, $statement);
  }

  public function update(Request $request, Response $response, $args) {
    $request_body           = $request->getParsedBody();
    $stripeSecretToken      = $request_body['stripe_secret_token'];
    $stripePublishableToken = $request_body['stripe_publishable_token'];
    $paypalToken            = $request_body['paypal_token'];

    if (!isset($stripeSecretToken) and !isset($stripePublishableToken) and !isset($paypalToken)) {
      return $this->handleRequest($response, 400, 'Data incorrect');
    }

    $query   = "UPDATE payment 
                SET stripe_secret_token = :stripe_secret_token, stripe_publishable_token = :stripe_publishable_token, 
                    paypal_token = :paypal_token 
                WHERE id = 1";
    $prepare = $this->db->prepare($query);

    $result = $prepare->execute([
                                  'stripe_secret_token'      => $stripeSecretToken,
                                  'stripe_publishable_token' => $stripePublishableToken,
                                  'paypal_token'             => $paypalToken,
                                ]);

    return $this->postSendResponse($response, $result, 'Data updated');
  }

}
