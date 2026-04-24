<?php

namespace App\Policies;

use App\Enums\PostStatusEnum;
use App\Enums\RoleEnum;
use App\Exceptions\BusinessException;
use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function view(User $user, Post $post): bool
    {
        return ($post->status === PostStatusEnum::PUBLISHED &&
            $post->user_id !== $user->id) ||
            $post->user_id === $user->id ||
            $user->role === RoleEnum::ADMIN;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id ||
            $user->role == RoleEnum::ADMIN;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id ||
            $user->role === RoleEnum::ADMIN;
    }

    public function markAsViewed(User $user, Post $post): bool
    {
        return $user->id !== $post->user->id;
    }
}
