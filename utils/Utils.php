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
}
