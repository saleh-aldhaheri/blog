<?php

use App\Enums\InteractionTypeEnum;
use App\Models\User;
use App\Models\Post;
use App\Models\Interaction;
use App\Services\Dashboard\Widgets\Engagements\LikesGrowthWidget;


it('returns likes growth grouped by action and date', function () {

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();
    $post = Post::factory()->create();

    Interaction::factory()->count(1)->create([
        'user_id' => $user1->id,
        'interactable_id' => $post->id,
        'interactable_type' => Post::class,
        'action' => InteractionTypeEnum::LIKE->value,
        'created_at' => '2026-04-27 10:00:00',
    ]);

    Interaction::factory()->count(1)->create([
        'user_id' => $user2->id,
        'interactable_id' => $post->id,
        'interactable_type' => Post::class,
        'action' => InteractionTypeEnum::LIKE->value,
        'created_at' => '2026-04-28 10:00:00',
    ]);

    Interaction::factory()->count(1)->create([
        'user_id' => $user3->id,
        'interactable_id' => $post->id,
        'interactable_type' => Post::class,
        'action' => InteractionTypeEnum::LOVE->value,
        'created_at' => '2026-04-28 10:00:00',
    ]);

    $result = app(LikesGrowthWidget::class)();

    expect($result)->toBeArray();
    expect($result)->not->toBeEmpty();

    expect($result[0])
        ->toHaveKeys([
            'action',
            'date',
            'total',
        ]);

    $mapped = collect($result)->map(fn($item) => [
        'action' => $item['action'],
        'date' => $item['date'],
        'total' => $item['total'],
    ])->values()->all();

    expect($mapped)->toContain([
        'action' => 'love',
        'date' => '2026-04-28',
        'total' => 1,
    ]);
});
