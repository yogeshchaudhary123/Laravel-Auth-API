<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\RefreshToken;
use Illuminate\Support\Facades\Hash;

class LogoutController extends Controller
{
    /**
     * User logout method
     *
     * @logout
     * @param Request $request
     *
     * @return Json
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                "error" => "User not found!"
            ], 404);
        }

        // delete the access token
        $request->user()->currentAccessToken()->delete();

        // optionally revoke the refresh token if provided in request
        $refresh = $request->input('refreshToken') ?? $request->bearerToken();
        if ($refresh) {
            // find stored refresh tokens for this user and match hash
            $hash = hash('sha256', $refresh);
            $rt = RefreshToken::where('user_id', $user->id)->where('token_hash', $hash)->first();
            if ($rt) {
                $rt->update(['revoked' => true]);
            }
        }

        // Delete expired tokens also
        // $user->tokens()->where('expires_at', '<', Carbon::now())->delete();

        return Helper::successResponse("Logged out successfully!");
    }
}
