<?php

use App\Enums\PostStatusEnum;
use App\Enums\RoleEnum;
use App\Models\User;
use App\Models\Post;
use App\Services\Dashboard\Widgets\Users\TopAuthorsWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns top authors ranked by published posts count', function () {

    $admin = User::factory()->create([
        'role' => RoleEnum::ADMIN->value,
    ]);

    $authorA = User::factory()->create([
        'role' => RoleEnum::USER->value,
    ]);

    Post::factory()->count(3)->create([
        'user_id' => $authorA->id,
        'status' => PostStatusEnum::PUBLISHED->value,
    ]);

    $authorB = User::factory()->create([
        'role' => RoleEnum::USER->value,
    ]);

    Post::factory()->count(1)->create([
        'user_id' => $authorB->id,
        'status' => PostStatusEnum::PUBLISHED->value,
    ]);

    $authorC = User::factory()->create([
        'role' => RoleEnum::USER->value,
    ]);

    Post::factory()->count(5)->create([
        'user_id' => $authorC->id,
        'status' => PostStatusEnum::DRAFT->value,
    ]);

    $result = app(TopAuthorsWidget::class)();

    expect(collect($result)->pluck('role'))
        ->not->toContain(RoleEnum::ADMIN->value);

    expect($result[0]['id'])->toBe($authorA->id);
    expect($result[1]['id'])->toBe($authorB->id);

    expect($result[0]['published_posts_count'])->toBe(3);
    expect($result[1]['published_posts_count'])->toBe(1);
});
