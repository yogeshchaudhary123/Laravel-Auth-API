<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Helpers\Helper;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TokenController extends Controller
{
    /**
     * Validate bearer token and return user data
     * Protected by auth:sanctum
     */
    public function validate(Request $request)
    {
        $user = Auth::user();

        if (!isset($user)) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        return Helper::successResponse('Token valid', new UserResource($user), 200);
    }
}
