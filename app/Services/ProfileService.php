<?php

namespace App\Services;

use App\Data\UpdateProfileData;
use App\Enums\BusinessExceptionsEnums;
use App\Exceptions\BusinessException;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    public function getProfile(): User
    {
        return auth()
            ->user()
            ->loadCount(['posts', 'viewedPosts', 'followers', 'followings'])
            ->load('avatar');
    }

    public function updateProfile(UpdateProfileData $data): User
    {
        if ($data->name === null && $data->email === null && $data->avatar === null) {
            throw new BusinessException(BusinessExceptionsEnums::INVALID, 'Provide at least one of: name, email, or avatar.');
        }

        $user = auth()->user();

        if ($data->name !== null) {
            $user->name = $data->name;
        }

        if ($data->email !== null) {
            $user->email = $data->email;
        }

        if ($data->avatar !== null) {
            $user->addMedia($data->avatar)->toMediaCollection('avatar');
        }

        $user->save();
        $user->fresh();
        return $this->getProfile();
    }

    public function changePassword(User $user, string $plainPassword): void
    {
        $user->password = $plainPassword;
        $user->save();
    }
}
