<?php

use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Pagination\CursorPaginator;

use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    $this->categoryService = new CategoryService;
});

describe('getCategory', function () {
    it('should get categories with respecting the limit', function ($limit) {

        Category::factory(20)->create();

        $categories = $this->categoryService->getCategories(limit: $limit);

        expect($categories)
            ->toBeInstanceOf(CursorPaginator::class)
            ->and($categories->items())->toHaveCount($limit);
    })->with([10, 5, 15]);

    it('should search category by Name', function () {

        Category::factory(20)->create();

        $name = 'uniqid name';

        Category::factory(1)->create([
            'name' => $name,
        ]);

        $categories = $this->categoryService->getCategories(search: $name);

        expect($categories)
            ->toBeInstanceOf(CursorPaginator::class)
            ->and($categories->items())->toHaveCount(1)
            ->and($categories->first()->name)->toBe($name);
    });
});

describe('storeCategory', function () {
    it('should store the category', function () {

        $name = fake()->title();

        $category = $this->categoryService->storeCategory($name);

        expect($category)->not()->toBeNull()
            ->and($category->name)->toBe($name);
    });
});

describe('updateComment', function () {
    it('should update the category name', function () {
        $name = fake()->text();

        $category = Category::factory(1)->create([
            'name' => 'old name',
        ])->first();

        $category = $this->categoryService->updateCategory($category, $name);

        expect($category)->not()->toBeNull()
            ->and($category->name)->toBe($name);
    });
});

describe('delete category', function () {
    it('should delete the category', function () {
        $category = Category::factory(1)->create([
            'name' => 'old name',
        ])->first();

        $this->categoryService->deleteCategory($category);

        assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    });
});
