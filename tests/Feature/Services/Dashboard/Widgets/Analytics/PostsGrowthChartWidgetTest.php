<?php

use App\Models\Post;
use App\Services\Dashboard\Widgets\Analytics\PostsGrowthChartWidget;


it('returns posts growth grouped by date', function () {

    Post::factory()->count(2)->create([
        'created_at' => '2026-04-27 10:00:00',
    ]);

    Post::factory()->count(3)->create([
        'created_at' => '2026-04-28 12:00:00',
    ]);

    Post::factory()->count(1)->create([
        'created_at' => '2026-04-29 15:00:00',
    ]);

    $result = app(PostsGrowthChartWidget::class)();

    expect($result)->toBe([
        [
            'total' => 2,
            'date' => '2026-04-27',
        ],
        [
            'total' => 3,
            'date' => '2026-04-28',
        ],
        [
            'total' => 1,
            'date' => '2026-04-29',
        ],
    ]);
});
