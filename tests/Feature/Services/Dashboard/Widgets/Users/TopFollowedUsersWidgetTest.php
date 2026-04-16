<?php

use App\Models\User;
use App\Services\Dashboard\Widgets\Users\TopFollowedUsersWidget;

it('returns users ordered by most followings', function () {

    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $userC = User::factory()->create();

    $followers = User::factory()->count(5)->create();

    $userA->followings()->attach([
        $followers[0]->id,
        $followers[1]->id,
        $followers[2]->id,
    ]);

    $userB->followings()->attach($followers[3]->id);


    $result = app(TopFollowedUsersWidget::class)();

    expect($result[0]['id'])->toBe($userA->id);
    expect($result[1]['id'])->toBe($userB->id);
    expect($result[2]['id'])->toBe($userC->id);

    expect($result[0]['followings_count'])->toBe(3);
    expect($result[1]['followings_count'])->toBe(1);
    expect($result[2]['followings_count'])->toBe(0);
});
