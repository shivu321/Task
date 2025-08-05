<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public static function sendSuccess($message = "", $statusCode = 200, $data = null,  $headers = [],)
    {
        return self::sendResponse($message, $data, true, $statusCode, $headers);
    }

    public static function sendError($message = "",  $statusCode = 400, $data = null, $headers = [],)
    {
        return self::sendResponse($message, $data, false, $statusCode, $headers);
    }

    private static function sendResponse($message = "", $data = null, $status = true, $statusCode = 200, $headers = [])
    {
        $response = array(
            "status" => $status,
            "message" => $message,
        );
        if ($data && $data != null) {
            if (is_array($data)) {
                $response = array_merge($response, $data);
            } else if ($data instanceof \Illuminate\Database\Eloquent\Model) {
                $response['info'] = $data;
            } else if ($data instanceof \Illuminate\Database\Eloquent\Collection) {
                $response['list'] = $data;
            } else if (is_string($data)) {
                $response['data'] = $data;
            }
        }
        return response()->json($response, $statusCode, $headers);
    }
}
