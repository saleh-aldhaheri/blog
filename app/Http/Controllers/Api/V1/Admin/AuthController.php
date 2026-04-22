<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\BusinessExceptionsEnums;
use App\Enums\RoleEnum;
use App\Exceptions\BusinessException;
use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends BaseController
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::firstWhere('email', $request->email);

        if (
            ! $user ||
            ! Auth::attempt($request->only('email', 'password')) ||
            $user->role !== RoleEnum::ADMIN
        ) {

            throw new BusinessException(BusinessExceptionsEnums::AUTH);
        }

        $token = $user->createToken(
            'admin-app',
            ['*'],
            now()->plus(days: 3)
        );

        return $this->apiResponse->success([
            'token' => $token->plainTextToken,
            'user' => $user,
        ], 'Login successful', 200);
    }

    public function logout(Request $request): Response
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->noContent();
    }
}
