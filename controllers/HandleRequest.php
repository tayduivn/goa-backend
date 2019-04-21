<?php
/**
 * Created by PhpStorm.
 * User: Ivans
 * Date: 16/03/2019
 * Time: 11:18
 */

namespace App\Controller;

require_once '../utils/Utils.php';
use App\Utils\Utils;
use Psr\Http\Message\ResponseInterface as Response;

class HandleRequest extends Utils {

  /**
   * @return mixed
   */
  public function getSession() {
    return new \SlimSession\Helper;
  }

  /**
   * @param Response $res
   * @param int      $statusCode
   * @param string   $body
   * @param string   $message
   * @return array
   */
  public function handleRequest(Response $res, $statusCode, $message = "", $body = "") {
    if ($statusCode === 200) {
      $res->withAddedHeader('Access-Control-Allow-Origin', '*');
      return $res->withJson([
                              'statusCode' => 200,
                              'message'    => $message ?: 'Success',
                              'data'       => $body,
                              'error'      => false
                            ], 200);

    } else if ($statusCode === 201) {
      return $res->withJson([
                              'statusCode' => 201,
                              'message'    => $message ?: 'Success',
                              'error'      => false
                            ], 201);

    } else if ($statusCode === 202) {
      return $res->withJson([
                              'statusCode' => 202,
                              'message'    => $message ?: 'Success',
                              'error'      => false
                            ], 202);

    } else if ($statusCode === 203) {
      return $res->withJson([
                              'statusCode' => 203,
                              'message'    => $message ?: 'Success',
                              'error'      => false
                            ], 203);

    } else if ($statusCode === 204) {
      return $res->withJson([
                              'statusCode' => 204,
                              'message'    => $message ?: 'Success',
                              'error'      => false
                            ], 204);

    } else if ($statusCode === 400) {
      return $res->withJson([
                              'statusCode' => 400,
                              'message'    => $message ?: 'Something went wrong',
                              'error'      => true
                            ], 400);

    } else if ($statusCode === 401) {
      return $res->withJson([
                              'statusCode' => 401,
                              'message'    => $message ?: 'Authorized',
                              'error'      => true
                            ], 401);

    } else if ($statusCode === 402) {
      return $res->withJson([
                              'statusCode' => 402,
                              'message'    => $message ?: 'Something went wrong',
                              'error'      => true
                            ], 402);

    } else if ($statusCode === 403) {
      return $res->withJson([
                              'statusCode' => 403,
                              'message'    => $message ?: 'Something went wrong',
                              'error'      => true
                            ], 403);

    } else if ($statusCode === 404) {
      return $res->withJson([
                              'statusCode' => 404,
                              'message'    => $message ?: 'No found',
                              'error'      => true
                            ], 404);

    } else if ($statusCode === 500) {
      return $res->withJson([
                              'statusCode' => 500,
                              'message'    => $message ?: 'Something went wrong',
                              'error'      => true
                            ], 500);

    } else {
      return [
        'statusCode' => 'unknown',
        'message'    => $message ?: 'Something went wrong',
        'error'      => true
      ];
    }
  }
}
