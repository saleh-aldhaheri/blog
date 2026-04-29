<?php

use App\Models\Post;
use App\Models\User;
use App\Models\Interaction;
use App\Services\Dashboard\Widgets\Contents\TopLikedPostsWidget;

it('returns top liked posts ordered by interactions count', function () {

    $user = User::factory()->create();

    $postA = Post::factory()->create([
        'user_id' => $user->id,
    ]);

    $postB = Post::factory()->create([
        'user_id' => $user->id,
    ]);

    $postC = Post::factory()->create([
        'user_id' => $user->id,
    ]);

    Interaction::factory()->count(5)->create([
        'interactable_id' => $postA->id,
        'interactable_type' => Post::class,
    ]);

    Interaction::factory()->count(2)->create([
        'interactable_id' => $postB->id,
        'interactable_type' => Post::class,
    ]);

    Interaction::factory()->count(8)->create([
        'interactable_id' => $postC->id,
        'interactable_type' => Post::class,
    ]);

    $result = app(TopLikedPostsWidget::class)();

    expect($result)->toHaveCount(3);

    expect($result[0]['interactions_count'])->toBe(8);
    expect($result[1]['interactions_count'])->toBe(5);
    expect($result[2]['interactions_count'])->toBe(2);

    expect($result[0])
        ->toHaveKeys([
            'user',
            'interactions_count',
            'comments_count',
        ]);
});
