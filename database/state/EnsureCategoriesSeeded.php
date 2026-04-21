<?php

namespace Database\State;

use App\Models\Category;

class EnsureCategoriesSeeded
{
    public function __invoke(): void
    {
        if ($this->present()) {
            return;
        }

        Category::insert([
            ['name' => 'General', 'slug' => 'general'],
            ['name' => 'Programming', 'slug' => 'programming'],
            ['name' => 'DevOps', 'slug' => 'devops'],
        ]);
    }

    private function present(): bool
    {
        return Category::whereIn('name', ['General', 'programming', 'DevOps'])->exists();
    }
}
