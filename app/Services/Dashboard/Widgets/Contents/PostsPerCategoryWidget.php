<?php

namespace App\Services\Dashboard\Widgets\Contents;

use App\Models\Category;

class PostsPerCategoryWidget
{
    public function __invoke()
    {
        return Category::withCount('posts')
            ->get()
            ->toArray();
    }
}
