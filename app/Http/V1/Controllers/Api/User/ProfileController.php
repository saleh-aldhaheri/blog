<?php

namespace App\Http\V1\Controllers\Api\User;

use App\Data\ChangePasswordData;
use App\Data\UpdateProfileData;
use App\Http\V1\Controllers\Api\BaseController;
use App\Http\V1\Resources\ProfileResource;
use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends BaseController
{
    public function __construct(
        private ProfileService $profileService
    ) {}

    /**
     * Get current profile
     *
     * Returns the authenticated user with relation counts (`posts_count`, `viewed_posts_count`, `followers_count`, `followings_count`) and `avatar` media when set.
     *
     * @group v1 /user
     *
     * @subgroup Profile
     *
     * @urlParam user integer required The user ID Example: 1
     *
     * @response 200 scenario=success {
     *   "data": {
     *     "id": 1,
     *     "name": "Jane",
     *     "email": "jane@example.com",
     *     "email_verified_at": "2026-01-15T12:00:00+00:00",
     *     "role": "user",
     *     "posts_count": 3,
     *     "viewed_posts_count": 5,
     *     "followers_count": 2,
     *     "followings_count": 4,
     *     "avatar": null
     *   }
     * }
     */
    public function show(User $user): JsonResponse
    {
        $user = $this->profileService->getProfile($user);

        return (new ProfileResource($user))->response();
    }

    /**
     * Update profile
     *
     * multipart/form-data request.
     *
     * At least one of `name`, `email`, or `avatar` is required.
     *
     * @group v1 /user
     *
     * @subgroup Profile
     *
     * @bodyParam name string optional Display name (2–256 chars). Example: Jane Doe
     * @bodyParam email string optional Must be unique among users. Example: new@example.com
     * @bodyParam avatar file optional Image (jpeg, png, gif). Max ~5MB.
     *
     * @response 200 scenario=success {
     *   "data": {
     *     "id": 1,
     *     "name": "Jane Doe",
     *     "email": "jane@example.com",
     *     "email_verified_at": "2026-01-15T12:00:00+00:00",
     *     "role": "user",
     *     "posts_count": 0,
     *     "viewed_posts_count": 0,
     *     "followers_count": 0,
     *     "followings_count": 0,
     *     "avatar": "https://example.com/storage/1/face.jpg"
     *   }
     * }
     * @response 422 scenario="no fields or validation" {
     *   "message": "Provide at least one of: name, email, or avatar.",
     *   "errors": []
     * }
     */
    public function update(UpdateProfileData $data): JsonResponse
    {
        $user = $this->profileService->updateProfile($data);

        return (new ProfileResource($user))->response();
    }

    /**
     * Change password
     *
     * Updates the password after verifying the current one.
     *
     * @group v1 /user
     *
     * @subgroup Profile
     *
     * @bodyParam current_password string required The existing password.
     * @bodyParam password string required New password (min 8). Example: newsecret12
     * @bodyParam password_confirmation string required Must match `password`.
     *
     * @response 204 scenario=success
     * @response 422 scenario="wrong current password" {
     *   "message": "The current password is incorrect.",
     *   "errors": {
     *     "current_password": ["The current password is incorrect."]
     *   }
     * }
     */
    public function updatePassword(ChangePasswordData $data): Response
    {
        $user = auth()->user();

        if (! Hash::check($data->current_password, $user->getAuthPassword())) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $this->profileService->changePassword($user, $data->password);

        return response()->noContent();
    }
}
