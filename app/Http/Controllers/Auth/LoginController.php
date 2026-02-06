<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Helpers\Helper;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\RefreshToken;

class LoginController extends Controller
{
    /**
     * User login
     *
     * @method login
     * @param LoginRequest request
     *
     * @return Json
     */
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return Helper::errorResponse('Invalid credentials');
        }

        // create short-lived access token
        $accessToken = $user->createToken('access_token')->plainTextToken;
        $user->tokens()->orderBy('created_at', 'desc')->first()->update(['expires_at' => now()->addHours(1)]);

        // create refresh token and store hash
        $plainRefresh = Str::random(128);
        RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainRefresh),
            'expires_at' => now()->addHours(1), // 1 hour expiry for refresh token
        ]);

        $user = $user->fresh();

        return response()->json([
            'accessToken' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600, // 1 hour in seconds
            'refreshToken' => $plainRefresh,
            'user' => $user,
        ]);
    }
}
