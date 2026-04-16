<?php

namespace App\Services\Dashboard\Widgets\Users;

use App\Enums\RoleEnum;
use App\Models\User;

class RecentUsersWidget
{

    public function __invoke()
    {
        return User::orderBy('created_at', 'desc')
            ->whereNot('role', RoleEnum::ADMIN->value)
            ->limit(10)
            ->get()
            ->toArray();
    }
}
