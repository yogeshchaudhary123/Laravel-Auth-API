<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Helpers\Helper;
use App\Models\RefreshToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RefreshController extends Controller
{
    /**
     * Exchange refresh token for new access token (and rotate refresh token)
     */
    public function refresh(Request $request)
    {
        $plain = $request->input('refreshToken');
        if (!$plain) {
            return Helper::errorResponse('refreshToken required', 400);
        }

        $hash = hash('sha256', $plain);

        $rt = RefreshToken::where('token_hash', $hash)->first();
        if (!$rt || $rt->revoked) {
            return Helper::errorResponse('Invalid refresh token', 401);
        }

        if ($rt->expires_at && $rt->expires_at->isPast()) {
            return Helper::errorResponse('Refresh token expired', 401);
        }

        $user = $rt->user;
        if (!$user) {
            return Helper::errorResponse('User not found', 404);
        }

        // create new access token
        $accessToken = $user->createToken('access_token')->plainTextToken;
        $user->tokens()->orderBy('created_at', 'desc')->first()->update(['expires_at' => now()->addMinutes($accessExpirationMinutes)]);

        // rotate refresh token: revoke old and create new one
        $rt->update(['revoked' => true]);
        $newPlain = Str::random(128);
        RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $newPlain),
            'expires_at' => now()->addHours(1),
        ]);

        return response()->json([
            'accessToken' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refreshToken' => $newPlain,
            'user' => $user->fresh(),
        ]);
    }
}
