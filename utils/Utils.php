<?php

namespace App\Utils;

use Braintree_Gateway;
use Slim\Http\UploadedFile;

/**
 * Created by PhpStorm.
 * User: Ivans
 * Date: 18/03/2019
 * Time: 9:21
 */
class Utils {

  function moveUploadedFile($directory, UploadedFile $uploadedFile) {
    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    try {
      $basename   = bin2hex(random_bytes(8));
      $filename   = sprintf('%s.%0.8s', $basename, $extension);
      $targetPath = $directory . DIRECTORY_SEPARATOR . $filename;
      $uploadedFile->moveTo($targetPath);
    } catch (\Exception $e) {
      return "Error";
    }
    return $filename;
  }

  function getBaseURL() {
    $heroku = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == "https";
    $server = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $args   = ($heroku) || ($server) ? 'https' : 'http';
    return sprintf("%s://%s", $args, $_SERVER['SERVER_NAME']);
  }

  function sendEmail($subject, $message, $emailRecipient, $emailSender = 'desarrollo.theroom@gmail.com') {
    $to      = $emailRecipient;
    $headers = 'From: ' . $emailSender . "\r\n" .
      'Reply-To: ' . $emailSender . "\r\n" .
      'X-Mailer: PHP/' . phpversion();

    mail($to, $subject, $message, $headers);
  }

  /**
   * @param       $db
   * @param       $product
   * @param array $result
   * @param       $index
   * @return array
   */
  function getImagesProducts($db, $product, array $result, $index) {
    $query     = "SELECT product_image.id AS id_image, image FROM product_image
                  INNER JOIN product p on product_image.product_id = p.id
                  WHERE product_image.active != 0 AND product_image.product_id = :id";
    $statement = $db->prepare($query);
    $statement->execute(['id' => $product['id']]);
    $resultImage = $statement->fetchAll();

    if (is_array($resultImage) and !empty($resultImage)) {
      $result[$index]['images'] = $resultImage;
    } else {
      $result[$index]['images'] = [['id_image' => '0', 'image' => 'http://goa-backend/src/uploads/no-image.png']];
    }
    return $result;
  }

  /**
   * @param $db
   * @param $product
   * @param $result
   * @param $index
   * @return mixed
   */
  function getCategoriesProducts($db, $product, $result, $index) {
    $query     = "SELECT category.id, category.name FROM category
                  INNER JOIN product_category pc on category.id = pc.category_id
                  WHERE category.active != 0 AND pc.product_id = :id";
    $statement = $db->prepare($query);
    $statement->execute(['id' => $product['id']]);
    $resultCategory = $statement->fetchAll();

    if (is_array($resultCategory) and !empty($resultCategory)) {
      $result[$index]['categories'] = $resultCategory;
    }
    return $result;
  }

  /**
   * @param $db
   * @param $cart_id
   * @param $result
   * @param $index
   * @return mixed
   */
  function getCartsProducts($db, $cart_id, $result, $index) {
    $query     = "SELECT 
                  cp.id AS cart_product_id, cp.quantity AS cart_quantity, cp.inserted_at, cp.updated_at, cp.cart_id, cp.product_id, 
                  p.id, p.sku, p.name, p.description_short, p.description_one, p.description_two, p.preparation, 
                  p.regular_price, p.quantity, p.active, p.inserted_at, p.updated_at, p.user_id
                  FROM cart_products cp INNER JOIN product p on cp.product_id = p.id
                  WHERE p.active != '0' AND cp.cart_id = :id";
    $statement = $db->prepare($query);
    $statement->execute(['id' => $cart_id]);
    $resultImage = $statement->fetchAll();

    if (is_array($resultImage) and !empty($resultImage)) {
      $result[$index]['products'] = $resultImage;
    }
    return $result;
  }

  /**
   * @param $db
   * @param $product
   * @param $result
   * @param $index
   * @return mixed
   */
  function getReviewsProducts($db, $product, $result, $index) {
    $query     = "SELECT product_review.id, product_review.title, product_review.stars, product_review.active, 
                  product_review.inserted_at, product_review.updated_at, product_review.user_id, 
                  product_review.product_id, product_review.message
                  FROM product_review
                  INNER JOIN product p on product_review.product_id = p.id
                  WHERE product_review.active != 0 AND p.id = :id";
    $statement = $db->prepare($query);
    $statement->execute(['id' => $product['id']]);
    $resultReview = $statement->fetchAll();

    if (is_array($resultReview) and !empty($resultReview)) {
      $result[$index]['reviews'] = $resultReview;
    }
    return $result;
  }

  /**
   * @param $cart_id
   * @param $db
   * @return string
   */
  public function isAlreadyCartOrder($cart_id, $db) {
    $statement = $db->prepare("SELECT * FROM `order` WHERE cart_id = :cartId");
    $statement->execute(['cartId' => $cart_id]);
    return !empty($statement->fetchAll());
  }

  /**
   * @param $db
   * @return Braintree_Gateway
   */
  public function gateWayPaypal($db) {
    $statement = $db->prepare("SELECT * FROM payment");
    $statement->execute();
    $result = $statement->fetchAll();
    return new Braintree_Gateway(['accessToken' => $result[0]['paypal_token'],]);
  }

  /**
   * @param $db
   * @param $tokenStripe
   * @param $total
   */
  public function postStripe($db, $tokenStripe, $total) {
    $statement = $db->prepare("SELECT * FROM payment");
    $statement->execute();
    $result = $statement->fetchAll();

    \Stripe\Stripe::setApiKey($result[0]['stripe_secret_token']);
    $customer = \Stripe\Customer::create([
                                           'email'  => $tokenStripe['email'],
                                           'source' => $tokenStripe['id'],
                                         ]);

    $charge = \Stripe\Charge::create([
                                       'customer'    => $customer->id,
                                       'description' => 'Custom t-shirt',
                                       'amount'      => str_replace(".", "", $total),
                                       'currency'    => 'usd',
                                     ]);
  }

  /**
   * @param $db
   * @param $total
   * @param $processor
   * @param $payloadPaypal
   * @return mixed
   */
  public function postPaypal($db, $total, $processor, $payloadPaypal) {
    $payer_info       = $payloadPaypal['payer_info'];
    $shipping_address = $payer_info['shipping_address'];
    $options          = [
      "amount"             => $total,
      'merchantAccountId'  => 'USD',
      "paymentMethodNonce" => $payloadPaypal['nonce'],
      "orderId"            => $payloadPaypal['orderID'],
      "shipping"           => [
        "firstName"         => isset($payer_info['first_name']) ? $payer_info['first_name'] : '',
        "lastName"          => isset($payer_info['last_name']) ? $payer_info['last_name'] : '',
        "company"           => isset($payer_info['company']) ? $payer_info['company'] : '',
        "streetAddress"     => isset($shipping_address['line1']) ? $shipping_address['line1'] : '',
        "extendedAddress"   => isset($shipping_address['line2']) ? $shipping_address['line2'] : '',
        "locality"          => isset($shipping_address['city']) ? $shipping_address['city'] : '',
        "region"            => isset($shipping_address['state']) ? $shipping_address['state'] : '',
        "postalCode"        => isset($shipping_address['postal_code']) ? $shipping_address['postal_code'] : '',
        "countryCodeAlpha2" => isset($shipping_address['country_code']) ? $shipping_address['country_code'] : '',
      ],
      "options"            => [
        "paypal" => [
          "customField" => "custom 1",
          "description" => $processor
        ],
      ]
    ];
    return $this->gateWayPaypal($db)->transaction()->sale($options);
  }
}
