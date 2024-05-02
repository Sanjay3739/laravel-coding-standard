<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\ApiHelper;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    public function userData()
    {
        try {
            $users = User::all();
            return response()->json(ApiHelper::response(true, $users, 'USER_FETCH_SUCCESS', 200));
        } catch (\Exception $e) {
            return response()->json(ApiHelper::response(false, [], 'INTERNAL_SERVER_ERROR', 500));
        }
    }
}   