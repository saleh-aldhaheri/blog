<?php

namespace App\Services;

use App\Enums\BusinessExceptionsEnums;
use App\Exceptions\BusinessException;
use App\Models\Category;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Str;

class CategoryService
{
    public function getCategories(string $search = '', int $limit = 10): CursorPaginator
    {
        return Category::query()
            ->search($search)
            ->orderBy('created_at', 'desc')
            ->orderBy('id')
            ->cursorPaginate($limit);
    }

    public function storeCategory(string $name): Category
    {
        return Category::create([
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }

    public function updateCategory(Category $category, string $name): Category
    {
        $category->update([
            'name' => $name,
            'slug' => Str::slug($name),
        ]);

        return $category;
    }

    public function deleteCategory(Category $category): void
    {
        if ($category->posts()->exists()) {
            throw new BusinessException(
                BusinessExceptionsEnums::INVALID,
                "Can't delete category with posts"
            );
        }

        $category->delete();
    }
}
