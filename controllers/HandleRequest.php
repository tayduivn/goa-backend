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
   * @param int      $status
   * @param string   $body
   * @param string   $message
   * @return array
   */
  public function handleRequest(Response $res, $status, $message = '', $body = '') {
    if ($status === 200) {
      return $res->withJson([
                              'status'  => 200,
                              'data'    => $body,
                              'message' => $message ?: 'Success',
                            ], 200);

    } else if ($status === 201) {
      return $res->withJson([
                              'status'  => 201,
                              'message' => $message ?: 'Success',
                            ], 201);

    } else if ($status === 201 && $body !== '') {
      return $res->withJson([
                              'status'  => 201,
                              'data'    => $body,
                              'message' => $message ?: 'Success',
                            ], 201);

    } else if ($status === 202) {
      return $res->withJson([
                              'status'  => 202,
                              'message' => $message ?: 'Success',
                            ], 202);

    } else if ($status === 203) {
      return $res->withJson([
                              'status'  => 203,
                              'message' => $message ?: 'Success',
                            ], 203);

    } else if ($status === 204) {
      return $res->withJson([
                              'status'  => 204,
                              'message' => $message ?: 'Sin datos',
                            ], 204);

    } else if ($status === 400) {
      return $res->withJson([
                              'status'  => 400,
                              'message' => $message ?: 'Something went wrong',
                            ], 400);

    } else if ($status === 401) {
      return $res->withJson([
                              'status'  => 401,
                              'message' => $message ?: 'Unauthorized',
                            ], 401);

    } else if ($status === 402) {
      return $res->withJson([
                              'status'  => 402,
                              'message' => $message ?: 'Something went wrong',
                            ], 402);

    } else if ($status === 403) {
      return $res->withJson([
                              'status'  => 403,
                              'message' => $message ?: 'Forbidden',
                            ], 403);

    } else if ($status === 404) {
      return $res->withJson([
                              'status'  => 404,
                              'message' => $message ?: 'No found',
                            ], 404);

    } else if ($status === 500) {
      return $res->withJson([
                              'status'  => 500,
                              'message' => $message ?: 'Something went wrong',
                            ], 500);

    } else {
      return [
        'status'  => 501,
        'message' => $message ?: 'Something went wrong',
      ];
    }
  }
}
