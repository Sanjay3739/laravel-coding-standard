<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Config;

class ApiHelper
{
    public static function response($success, $data = [], $messageKey = null, $status = 200)
    {
        $message = Config::get('constant.' . $messageKey);
        return [
            'success' => $success,
            'data' => $data,
            'message' => $message,
            'status' => $status,
        ];
    }
}
