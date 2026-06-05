<?php

use App\Enums\RoleEnum;
use App\Jobs\ClearNotificationJob;
use App\Models\User;
use App\Notifications\NewFollowerNotification;

it('clears notifications older than 7 days', function () {
    $user = CreateUserAs(RoleEnum::USER);

    $oldFollower = User::factory()->create();
    $newFollower = User::factory()->create();

    $this->travel(-10)->days();
    $user->notify(new NewFollowerNotification($oldFollower));

    $this->travelBack();

    $this->travel(-2)->days();
    $user->notify(new NewFollowerNotification($newFollower));

    $this->travelBack();

    ClearNotificationJob::dispatchSync($user);

    expect(
        $user->notifications()->where('notifiable_id', $user->id)
            ->where('created_at', '<=', now()->subDays(7))
            ->count()
    )->toBe(0);

    expect($user->notifications()->count())->toBe(1);
});
