<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;
use PhpParser\Node\Stmt\TryCatch;
use Tymon\JWTAuth\Facades\JWTAuth;

class Login extends Controller
{
    public function userLogin(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return Response::json(['message' => 'Invalid credentials'], 401);
        }

        return Response::json([
            'message' => 'User authenticated',
            'token' => $token,
        ], 200);
    }

    public function verifyJWT(Request $request)
    {
        return Response::json(['message' => 'User authenticated'], 200);
    }
}
