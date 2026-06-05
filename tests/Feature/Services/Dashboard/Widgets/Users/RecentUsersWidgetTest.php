<?php

use App\Enums\RoleEnum;
use App\Models\User;
use App\Services\Dashboard\Widgets\Users\RecentUsersWidget;

it('returns recent non-admin users ordered by latest', function () {

    User::factory()->create([
        'role' => RoleEnum::ADMIN->value,
        'created_at' => now()->subDays(1),
    ]);

    $users = User::factory()
        ->count(5)
        ->sequence(fn ($seq) => [
            'role' => RoleEnum::USER->value,
            'created_at' => now()->subMinutes(5 - $seq->index),
        ])
        ->create();

    $result = app(RecentUsersWidget::class)();

    expect(collect($result)->pluck('role'))
        ->not->toContain(RoleEnum::ADMIN->value);

    expect($result)->toHaveCount(5);

    expect($result[0]['id'])
        ->toBe($users->last()->id);

    expect($result[4]['id'])
        ->toBe($users->first()->id);
});
