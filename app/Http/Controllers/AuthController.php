<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    //

    public function login(Request  $request)
    {
        // Check User Credentials For Login
        if (Auth::attempt($request->only(['email', 'password']))) {
            // create token for logged user
            $token = Auth::user()->createToken($request->input('email'))->plainTextToken;

            return response()->json(['result' => true, "user" => Auth::user(), "token" => $token], 200);
        }
        return response()->json(["result" => false, "error" => "پسورد یا ایمل نادرست میباشد!"], 401);
    }

    public function logout(Request $request)
    {
        try {
            Auth::user()->tokens()->delete();
            return response()->json('از سیستم موفقانه خارج شدید!', 401);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }
}
