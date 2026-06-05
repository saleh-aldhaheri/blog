<?php

use App\Enums\RoleEnum;
use App\Models\User;
use App\Notifications\NewFollowerNotification;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = CreateUserAs(RoleEnum::USER);
    Sanctum::actingAs($this->user);

    $followers = User::factory(20)->create();

    $followers->each(function ($follower) {
        $this->user->notify(new NewFollowerNotification($follower));
    });
});

describe('get notifications', function () {
    it('should be able to return the notifications with respecting the limits', function ($limit) {

        $response = $this->getJson(route('api.user.notifications', ['limit' => $limit]))
            ->assertOk();
        expect($response->json('data'))->toHaveCount($limit);
    })->with([10, 5, 20]);
});

describe('mark notifications as read', function () {
    it('should be allowed to mark notification as read', function () {
        $notification = $this->user->notifications()->first();

        expect($notification->read_at)->toBeNull();

        $this->putJson(route('api.user.notifications.update', ['notification' => $notification]))
            ->assertNoContent();

        $notification->refresh();

        expect($notification->read_at)->not->toBeNull();
    });

    it('should allowed user to updated his own notification', function () {
        $notification = $this->user->notifications()->first();
        $newUser = CreateUserAs(RoleEnum::USER);
        $this->actingAs($newUser);
        $this->putJson(route('api.user.notifications.update', ['notification' => $notification]))
            ->assertForbidden();
    });
});
