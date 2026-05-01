<?php

use App\Enums\RoleEnum;
use App\Models\User;
use App\Notifications\NewFollowerNotification;

use function Pest\Laravel\assertDatabaseHas;

it('should assign notification to the user', function () {

    $user = CreateUserAs(RoleEnum::USER);

    $followers = User::factory(20)->create();

    $followers->each(function ($follower) use ($user) {
        $user->notify(new NewFollowerNotification($follower));
    });

    expect($user->notifications)->toHaveCount(20);

    assertDatabaseHas('notifications', [
        'notifiable_id' => $user->id,
        'type' => NewFollowerNotification::class,
    ]);
});
