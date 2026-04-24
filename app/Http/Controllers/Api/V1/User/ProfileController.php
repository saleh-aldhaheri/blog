<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Data\ChangePasswordData;
use App\Data\UpdateProfileData;
use App\Http\Controllers\Api\BaseController;
use App\Services\ProfileService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends BaseController
{
    public function __construct(
        ApiResponse $apiResponse,
        public ProfileService $profileService
    ) {
        parent::__construct($apiResponse);
    }

    public function show(): JsonResponse
    {
        $user = $this->profileService->getProfile();

        return $this->apiResponse->success(
            data: $user,
            message: '',
            code: 200
        );
    }

    public function update(UpdateProfileData $data): JsonResponse
    {
        $user = $this->profileService->updateProfile($data);

        return $this->apiResponse->success(
            data: $user,
            message: 'Profile updated successfully',
            code: 200
        );
    }

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
