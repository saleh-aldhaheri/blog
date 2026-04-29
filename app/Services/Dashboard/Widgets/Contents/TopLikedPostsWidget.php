<?php

namespace App\Services\Dashboard\Widgets\Contents;

use App\Models\Post;

class TopLikedPostsWidget
{
    /**
     * Invoke the class instance.
     */
    public function __invoke(): array
    {
        return Post::with('user')
            ->withCount('interactions')
            ->withCount('comments')
            ->orderBy('interactions_count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
