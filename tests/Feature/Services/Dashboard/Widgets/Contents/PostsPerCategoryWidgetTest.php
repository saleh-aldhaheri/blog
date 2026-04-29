<?php

use App\Models\Post;
use App\Models\Category;
use App\Services\Dashboard\Widgets\Contents\PostsPerCategoryWidget;

it('returns posts count per category', function () {

    $categoryA = Category::factory()->create();
    $categoryB = Category::factory()->create();

    Post::factory()->count(3)->create([
        'category_id' => $categoryA->id,
    ]);

    Post::factory()->count(2)->create([
        'category_id' => $categoryB->id,
    ]);

    $result = app(PostsPerCategoryWidget::class)();

    expect($result)->toHaveCount(2);

    expect($result[0]['posts_count'])
        ->toBeGreaterThanOrEqual(0);

    expect(collect($result)->pluck('posts_count')->sort()->values()->all())
        ->toEqual([2, 3]);
});
