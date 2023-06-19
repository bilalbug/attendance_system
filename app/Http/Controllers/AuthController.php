<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    //
    public function login(Request $request) {
        $credentials = $request->only('email', 'password');
        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error'=>'Invalid credentials'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error'=>'Couldn"t create token'], 500);
        }

        return response()->json(compact('token'));

    }

    public function logout () {
        Auth::logout();
        return response()->json(['message'=>'Successfully logged out']);
    }
}
