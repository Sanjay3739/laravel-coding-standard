<?php

namespace App\Http\Controllers;

use App\Helpers\ApiHelper;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userName' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = User::create([
            'userName' => $request->input('userName'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'password' => bcrypt($request->input('password')),
        ]);

        $token = JWTAuth::fromUser($user);

        // Using ApiHelper to create a custom API response
        $response = ApiHelper::response(true, ['user' => $user, 'token' => $token], 'USER_REGISTERED_SUCCESSFULLY', 201);

        return response()->json($response);
    }
    
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);
    
        if ($validator->fails()) {
            return response()->json(ApiHelper::response(false, [], 'INVALID_INPUT', 422));
        }
    
        $credentials = $request->only('email', 'password');
    
        $user = User::where('email', $credentials['email'])->first();
    
        if (!$user) {
            return response()->json(ApiHelper::response(false, [], 'USER_NOT_FOUND', 404));
        }
    
        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(ApiHelper::response(false, [], 'INVALID_CREDENTIAL', 400));
            }
        } catch (JWTException $e) {
            return response()->json(ApiHelper::response(false, [], 'TOKEN_NOT_CREATED', 500));
        }
    
        $user = JWTAuth::user();
    
        $response = ApiHelper::response(true, ['user' => $user, 'token' => $token], 'USER_LOGIN_SUCCESSFULLY', 201);
    
        return response()->json($response);
    }
    

    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate($request->token);
            return response()->json(ApiHelper::response(true, [], 'USER_LOGGEDOUT_SUCCESSFULLY', 201));
        } catch (JWTException $e) {
            return response()->json(ApiHelper::response(false, [], 'USER_LOGOUT_FAILED', 500));
        }
    }
    
    

}