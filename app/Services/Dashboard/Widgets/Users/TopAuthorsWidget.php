<?php

namespace App\Services\Dashboard\Widgets\Users;

use App\Enums\PostStatusEnum;
use App\Enums\RoleEnum;
use App\Models\User;

class TopAuthorsWidget
{
    public function __invoke()
    {
        return User::withCount([
            'posts as published_posts_count' => function ($query) {
                $query->where('status', PostStatusEnum::PUBLISHED->value);
            },
        ])
            ->whereNot('role', RoleEnum::ADMIN->value)
            ->orderBy('published_posts_count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
