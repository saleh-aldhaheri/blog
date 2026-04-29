<?php

namespace App\Services\Dashboard\Widgets\Contents;

use App\Models\Post;

class RecentPostsWidget
{
    /**
     * Create a new class instance.
     */
    public function __invoke()
    {
        return Post::with('user')
            ->withCount('interactions')
            ->withCount('comments')
            ->orderBy('created_at')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
