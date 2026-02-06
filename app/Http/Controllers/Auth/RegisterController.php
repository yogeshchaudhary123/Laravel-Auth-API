<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\RefreshToken;

class RegisterController extends Controller
{
    /**
     * User registration
     *
     * @method register
     * @param RegisterRequest $request
     *
     * @return Json
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            "name"     => $request->name,
            "email"    => $request->email,
            "password" => Hash::make($request->password),
        ]);

        // create short-lived access token (e.g., 15 minutes)
        $accessToken = $user->createToken('access_token')->plainTextToken;
        $user->tokens()->orderBy('created_at', 'desc')->first()->update(['expires_at' => now()->addHours(1)]);

        // create long-lived refresh token (store only hash)
        $plainRefresh = Str::random(128);
        RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainRefresh),
            'expires_at' => now()->addHours(1), // 1 hour expiry for refresh token
        ]);

        $user = $user->fresh();

        // return both tokens (client should store refreshToken securely)
        return response()->json([
            'accessToken' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600, // 1 hour in seconds
            'refreshToken' => $plainRefresh,
            'user' => $user,
        ]);
    }
}
