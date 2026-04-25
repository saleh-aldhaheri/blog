<?php

namespace App\Http\V1\Controllers\Api\User;

use App\Data\ChangePasswordData;
use App\Data\UpdateProfileData;
use App\Http\V1\Controllers\Api\BaseController;
use App\Services\ProfileService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends BaseController
{
    public function __construct(
        ApiResponse $apiResponse,
        private ProfileService $profileService
    ) {
        parent::__construct($apiResponse);
    }

    /**
     * Get current profile
     *
     * Returns the authenticated user with relation counts (`posts_count`, `viewed_posts_count`, `followers_count`, `followings_count`) and `avatar` media when set.
     *
     * @group v1 /user
     *
     * @subgroup Profile
     *
     * @response 200 scenario=success {
     *   "message": "",
     *   "data": {
     *     "id": 1,
     *     "name": "Jane",
     *     "email": "jane@example.com",
     *     "posts_count": 3,
     *     "viewed_posts_count": 5,
     *     "followers_count": 2,
     *     "followings_count": 4,
     *     "avatar": null
     *   }
     * }
     */
    public function show(): JsonResponse
    {
        $user = $this->profileService->getProfile();

        return $this->apiResponse->success(
            data: $user,
            message: '',
            code: 200
        );
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
     *   "message": "Profile updated successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "Jane Doe",
     *     "email": "jane@example.com",
     *     "posts_count": 0,
     *     "viewed_posts_count": 0,
     *     "followers_count": 0,
     *     "followings_count": 0
     *   }
     * }
     */
    public function update(UpdateProfileData $data): JsonResponse
    {
        $user = $this->profileService->updateProfile($data);

        return $this->apiResponse->success(
            data: $user,
            message: 'Profile updated successfully',
            code: 200
        );
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
     * @response 200 scenario=success {
     *   "message": "Password changed successfully",
     *   "data": null
     * }
     * @response 422 scenario="wrong current password" {
     *   "message": "The current password is incorrect.",
     *   "errors": {
     *     "current_password": ["The current password is incorrect."]
     *   }
     * }
     */
    public function updatePassword(ChangePasswordData $data): JsonResponse
    {
        $user = auth()->user();

        if (! Hash::check($data->current_password, $user->getAuthPassword())) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $this->profileService->changePassword($user, $data->password);

        return $this->apiResponse->success(
            data: null,
            message: 'Password changed successfully',
            code: 200
        );
    }
}
