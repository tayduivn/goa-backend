<?php

namespace App\Utils;

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
    return sprintf("%s://%s",
                   isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
                   $_SERVER['SERVER_NAME']);
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
    $statement = $db->prepare("SELECT product_image.id AS id_image, image FROM product_image
                                        INNER JOIN product p on product_image.product_id = p.id
                                        WHERE product_image.active != 0 AND product_image.product_id = :id");
    $statement->execute(['id' => $product['id']]);
    $resultImage = $statement->fetchAll();

    if (is_array($resultImage) and !empty($resultImage)) {
      $result[$index]['images'] = $resultImage;
    } else {
      $result[$index]['images'] = [['id_image' => 0, 'image' => 'http://goa-backend/src/uploads/no-image.png']];
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
    $statement = $db->prepare("SELECT category.id, category.name FROM category
                                        INNER JOIN product_category pc on category.id = pc.category_id
                                        WHERE category.active != 0 AND pc.product_id = :id");
    $statement->execute(['id' => $product['id']]);
    $resultCategory = $statement->fetchAll();

    if (is_array($resultCategory) and !empty($resultCategory)) {
      $result[$index]['categories'] = $resultCategory;
    }
    return $result;
  }

  /**
   * @param $db
   * @param $cart
   * @param $result
   * @param $index
   * @return mixed
   */
  function getCartsProducts($db, $cart, $result, $index) {
    $query     = "SELECT 
                        cp.id, cp.quantity AS cart_quantity, cp.inserted_at, cp.updated_at, cp.cart_id, cp.product_id, 
                        p.id, p.sku, p.name, p.description_short, p.description_one, p.description_two, p.preparation, 
                        p.regular_price, p.quantity, p.active, p.inserted_at, p.updated_at, p.user_id
                        FROM cart_products cp INNER JOIN product p on cp.product_id = p.id
                        WHERE p.active != '0' AND cp.cart_id = :id";
    $statement = $db->prepare($query);
    $statement->execute(['id' => $cart['cart_id']]);
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
    $statement = $db->prepare("SELECT product_review.id, product_review.title, product_review.stars, product_review.active, 
                              product_review.inserted_at, product_review.updated_at, product_review.user_id, 
                              product_review.product_id, product_review.message
                              FROM product_review
                              INNER JOIN product p on product_review.product_id = p.id
                              WHERE product_review.active != 0 AND p.id = :id");
    $statement->execute(['id' => $product['id']]);
    $resultReview = $statement->fetchAll();

    if (is_array($resultReview) and !empty($resultReview)) {
      $result[$index]['reviews'] = $resultReview;
    }
    return $result;
  }
}
