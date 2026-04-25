<?php

namespace App\Http\V1\Controllers\Api\Admin;

use App\Enums\BusinessExceptionsEnums;
use App\Enums\RoleEnum;
use App\Exceptions\BusinessException;
use App\Http\V1\Controllers\Api\BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends BaseController
{
    /**
     * Login (admin)
     *
     * Issue a Sanctum token for an account with role `admin`.
     *
     * @group v1 /admin
     *
     * @subgroup Auth
     *
     * @unauthenticated
     *
     * @bodyParam email string required Example: admin@example.com
     * @bodyParam password string required Min 8 characters.
     *
     * @response 200 scenario=success {
     *   "message": "Login successful",
     *   "data": {
     *     "token": "2|abcdefghijklmnopqrstuvwxyz",
     *     "user": {
     *       "id": 1,
     *       "name": "Admin",
     *       "email": "admin@example.com",
     *       "role": "admin"
     *     }
     *   }
     * }
     * @response 401 scenario="invalid credentials" {
     *   "message": "unauthenticated",
     *   "errors": []
     * }
     */
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

    /**
     * Logout (admin)
     *
     * Revoke the current access token.
     *
     * @group v1 /admin
     *
     * @subgroup Auth
     *
     * @authenticated
     *
     * @response 204 scenario=success
     */
    public function logout(Request $request): Response
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->noContent();
    }
}
