<?php

namespace App\Policies;

use App\Enums\PostStatusEnum;
use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function view(User $user, Post $post): bool
    {
        return ($post->status === PostStatusEnum::PUBLISHED &&
            $post->user_id !== $user->id) ||
            $post->user_id === $user->id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function markAsViewed(User $user, Post $post): bool
    {
        return $user->id !== $post->user->id;
    }
}
