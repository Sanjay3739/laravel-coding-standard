<?php

namespace App\Helpers;

class ApiHelper
{
    public static function response($success, $data = [], $message = null, $status = 200)
    {
        return [
            'success' => $success,
            'data' => $data,
            'message' => $message,
            'status' => $status,
        ];
    }
}
