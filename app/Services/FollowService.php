<?php

namespace App\Services;

use App\Enums\BusinessExceptionsEnums;
use App\Exceptions\BusinessException;
use App\Models\User;
use Illuminate\Pagination\CursorPaginator;

class FollowService
{
    public function getFollowings(int $limit): CursorPaginator
    {
        return auth()->user()
            ->followings()
            ->orderBy('created_at')
            ->orderBy('id')
            ->cursorPaginate($limit);
    }

    public function getFollowers(int $limit): CursorPaginator
    {
        return auth()->user()
            ->followers()
            ->orderBy('created_at')
            ->orderBy('id')
            ->cursorPaginate($limit);
    }

    public function follow(User $user): void
    {
        $auth = auth()->user();

        if ($auth->id === $user->id) {
            throw new BusinessException(BusinessExceptionsEnums::INVALID, 'Cannot follow yourself');
        }

        $auth->followings()->syncWithoutDetaching([$user->id]);
    }

    public function unfollow(User $user): void
    {
        auth()->user()->followings()->detach($user->id);
    }
}
