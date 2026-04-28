<?php

namespace App\Http\V1\Controllers\Api\User;

use App\Enums\BusinessExceptionsEnums;
use App\Enums\RoleEnum;
use App\Exceptions\BusinessException;
use App\Http\V1\Controllers\Api\BaseController;
use App\Http\V1\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends BaseController
{
    /**
     * Login (user)
     *
     * Issue a Sanctum personal access token for a user account (`role` must be `user`).
     *
     * @group v1 /user
     *
     * @subgroup Auth
     *
     * @unauthenticated
     *
     * @bodyParam email string required The account email. Example: jane@example.com
     * @bodyParam password string required Minimum 8 characters. Example: secretpass
     *
     * @response 200 scenario=success {
     *   "data": {
     *     "token": "1|abcdefghijklmnopqrstuvwxyz",
     *     "user": {
     *       "id": 1,
     *       "name": "Jane",
     *       "email": "jane@example.com",
     *       "email_verified_at": "2026-01-15T12:00:00.000000Z",
     *       "role": "user"
     *     }
     *   }
     * }
     * @response 401 scenario="invalid credentials" {
     *   "message": "Incorrect Credentials",
     *   "errors": []
     * }
     * @response 422 scenario="validation" {
     *   "message": "The email field is required.",
     *   "errors": { "email": ["The email field is required."] }
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
            $user->role !== RoleEnum::USER
        ) {
            throw new BusinessException(BusinessExceptionsEnums::AUTH, 'Incorrect Credentials');
        }

        $token = $user->createToken(
            'user-app',
            ['*'],
            now()->plus(weeks: 1)
        );

        return response()->json([
            'data' => [
                'token' => $token->plainTextToken,
                'user' => new UserResource($user),
            ],
        ], 200);
    }

    /**
     * Register (user)
     *
     * Create a new user with role `user`.
     *
     * @group v1 /user
     *
     * @subgroup Auth
     *
     * @unauthenticated
     *
     * @bodyParam name string required Display name (2–256 chars). Example: Jane Doe
     * @bodyParam email string required Must be unique. Example: jane@example.com
     * @bodyParam password string required Min 8 characters. Example: secretpass
     * @bodyParam password_confirmation string required Must match `password`.
     *
     * @response 201 scenario=success {
     *   "data": {
     *     "id": 2,
     *     "name": "Jane Doe",
     *     "email": "jane@example.com",
     *     "role": "user"
     *   }
     * }
     * @response 422 scenario="validation" {
     *   "message": "The name field is required.",
     *   "errors": { "name": ["The name field is required."] }
     * }
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'name' => ['required', 'string', 'min:2', 'max:256'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'password' => Hash::make($validated['password']),
            'email' => $validated['email'],
            'role' => RoleEnum::USER->value,
        ]);

        return new UserResource($user)->response();
    }

    /**
     * Logout (user)
     *
     * Revoke the current access token.
     *
     * @group v1 /user
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

        if ($token && $token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->noContent();
    }
}
