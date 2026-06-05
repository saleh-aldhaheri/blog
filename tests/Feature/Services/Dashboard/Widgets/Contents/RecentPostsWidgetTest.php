<?php

use App\Models\Comment;
use App\Models\Interaction;
use App\Models\Post;
use App\Models\User;
use App\Services\Dashboard\Widgets\Contents\RecentPostsWidget;

it('returns recent posts with relations and counts', function () {

    $user = User::factory()->create();

    $posts = Post::factory()
        ->count(12)
        ->sequence(fn ($sequence) => [
            'user_id' => $user->id,
            'created_at' => now()->subMinutes(12 - $sequence->index),
        ])
        ->create();

    Interaction::factory()->count(3)->create([
        'interactable_id' => $posts[0]->id,
        'interactable_type' => Post::class,
    ]);

    Comment::factory()->count(2)->create([
        'post_id' => $posts[0]->id,
    ]);

    $result = app(RecentPostsWidget::class)();

    expect($result)->toHaveCount(10);

    expect($result[0])
        ->toHaveKeys([
            'user',
            'interactions_count',
            'comments_count',
        ]);

    expect($result[0]['interactions_count'])->toBe(3);
    expect($result[0]['comments_count'])->toBe(2);
});
