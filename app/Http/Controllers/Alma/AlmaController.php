<?php

namespace App\Http\Controllers\Alma;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Input;

class AlmaController extends \App\Http\Controllers\Controller {

  /**
   * Handles errors
   *
   * @param string $message
   * @param int $statusCode
   * @return \Illuminate\Http\JsonResponse
   */
  public function error($message = 'Service unavailable', $statusCode = 400)
  {
    if (Input::has('callback')) {
      return Response::json($message, $statusCode)->setCallback(Input::get('callback'));
    } else {
      return Response::json($message, $statusCode);
    }
  }

  /**
   * Handles successful responses
   *
   * @param string $message
   * @return \Illuminate\Http\JsonResponse
   */
  public function ok($message = '')
  {
    if (Input::has('callback')) {
      return Response::json($message)->setCallback(Input::get('callback'));
    } else {
      return Response::json($message);
    }
  }
}
