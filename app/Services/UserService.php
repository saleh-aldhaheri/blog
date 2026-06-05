<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Notifications\NewFollowerNotification;
use App\Notifications\PostCreatedNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\CursorPaginator;

class UserService
{
    public function __construct(private ProfileService $profileService) {}

    public function getUsers(string $search = '', int $limit = 10): CursorPaginator
    {
        return User::search($search)
            ->orderBy('created_at', 'Desc')
            ->whereNot('role', RoleEnum::ADMIN->value)
            ->orderBy('id')
            ->cursorPaginate($limit);
    }

    public function getUser(User $user)
    {
        return $this->profileService->getProfile($user);
    }

    public function deleteUser(User $user): void
    {
        $user->delete();
    }

    public function getFollowingNotifications(int $limit = 10): CursorPaginator
    {
        return auth()
            ->user()
            ->notifications()
            ->whereNull('read_at')
            ->select('data', 'id')
            ->whereIn('type', [NewFollowerNotification::class, PostCreatedNotification::class])
            ->orderBy('created_at', 'desc')
            ->cursorPaginate($limit);
    }

    public function markNotificationAsRead(DatabaseNotification $notification): void
    {
        $notification->markAsRead();
    }
}
