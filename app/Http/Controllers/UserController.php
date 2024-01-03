<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getUserDetail(Request $request)
    {
        $user_id = JWTAuth::parseToken()->authenticate();

        $user = User::where('id', $user_id->id)->first();

        if (!$user) {
            return Response::json(['message' => 'User not found'], 404);
        }

        unset($user->password);
        return Response::json(['message' => 'User found', 'user' => $user], 200);
    }
}
