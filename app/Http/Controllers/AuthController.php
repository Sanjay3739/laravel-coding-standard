<?php

namespace App\Http\Controllers;

use App\Helpers\ApiHelper;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\ForgotPasswordMail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
            $validator = Validator::make($request->all(), [
                'userName' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'phone' => 'required|string|max:255|digits:10',
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

    public function forgotPassword(Request $request)
    {
        $email = $request->input('email');

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json(ApiHelper::response(false, [], 'USER_NOT_FOUND', 404));
        }

        $token = Str::random(60);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['email' => $user->email, 'token' => $token, 'created_at' => Carbon::now()]
        );

        // Create the reset link
        $link = 'http://localhost:3000/reset?token=' . $token;

        // Send email with password reset link
        Mail::to($user->email)->send(new ForgotPasswordMail($link));

        // Return a custom success response
        return response()->json(ApiHelper::response(true, [
            'email' => $user->email,
            'reset_link' => $link,
        ], 'PASSWORD_RESET_EMAIL_SENT', 200));
    }

    public function resetPassword(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
            'token' => 'required'
        ]);
        

        $token = $validatedData['token'];

        $user = DB::table('password_reset_tokens')->where('token', $token)->first();
        if (!$user) {
            return response()->json(ApiHelper::response(false, [], 'INVALID_TOKEN', 404));
        }

        // Update user's password
        $user = User::where('email', $user->email)->first();
        $user->password = Hash::make($validatedData['password']);
        $user->save();

        // Delete the token from the password resets table
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        // Return a custom success response
        return response()->json(ApiHelper::response(true, [], 'PASSWORD_RESET_SUCCESS', 200));
    }
}