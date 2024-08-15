<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * GET api/login
     *
     * This endpoint allows users to log in by providing their email and password. If the credentials are valid, a JWT access token is returned.
     *
     * @bodyParam email string required The email of the user. Example: superadmin@example.com
     * @bodyParam password string required The password of the user. Example: password
     *
     * @response 200 {
     *  "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
     * }
     *
     * @response 401 {
     *  "message": "Invalid credentials."
     * }
     * 
     * @before
     * Generate a token for use in examples.
     *
     * @var string $token = Auth::attempt(['email' => 'superadmin@example.com', 'password' => 'password']);
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json(['access_token' => $token]);
    }

    /**
     * Log out from the application
     *
     * This endpoint allows users to log out by providing their email and password.
     *
     * @bodyParam email string required The email of the user. Example: superadmin@example.com
     * @bodyParam password string required The password of the user. Example: password
     *
     * @headers {
     *  "Authorization": "Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9"
     * }
     * 
     * @response 200 {
     *  "message": "Logged out successfully" 
     * }
     *
     * @response 401 {
     *  "message": "Invalid credentials."
     * }
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
