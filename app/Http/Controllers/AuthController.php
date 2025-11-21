<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Minimal response
        return response()->json([
            'message' => 'Login route is working',
            'data' => $request->all() // Echo back request data for testing
        ]);
    }
}
