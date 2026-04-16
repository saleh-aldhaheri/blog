<?php

namespace App\Services\Dashboard\Widgets\Users;

use App\Models\User;

class TopFollowedUsersWidget
{

    public function __invoke(): array
    {
        return  User::withCount('followings')
            ->orderBy('followings_count', 'desc')
            ->get()
            ->toArray();
    }
}
