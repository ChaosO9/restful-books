<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class Register extends Controller
{
    public function userRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Your email has already been taken',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $role = Role::where('name', 'user')->where('guard_name', 'api')->first();
            if (!$role) {
                $role = Role::create(['name' => 'user', "guard_name" => "api"]);
            }

            $role = Role::where('name', 'admin')->where('guard_name', 'api')->first();
            if (!$role) {
                $role = Role::create(['name' => 'admin', "guard_name" => "api"]);
            }

            $user->assignRole('user');

            return response()->json([
                'message' => 'User created',
                'user' => $user,
            ], 201);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 500);
        }
    }
}
