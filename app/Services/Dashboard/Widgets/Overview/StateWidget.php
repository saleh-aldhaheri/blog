<?php

namespace App\Services\Dashboard\Widgets\Overview;

use App\Models\Comment;
use App\Models\Interaction;
use App\Models\Post;
use App\Models\User;

class StateWidget
{
    public function __invoke()
    {
        return  [
            'users' => User::count(),
            'posts' => Post::count(),
            'comments' => Comment::count(),
            'interactions' => Interaction::count()
        ];
    }
}
