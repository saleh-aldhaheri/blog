<?php

use App\Enums\RoleEnum;
use App\Models\Post;
use App\Models\User;
use App\Notifications\PostCreatedNotification;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\assertDatabaseHas;

it('should create post created notification to the following users', function () {
    $user = CreateUserAs(RoleEnum::USER);

    $followers = User::factory(20)->create();

    $post = Post::factory()->create([
        'user_id' => $user->id,
    ]);

    $user->followers()->attach(
        $followers->pluck('id')
    );

    Notification::send(
        $followers,
        new PostCreatedNotification($user, $post)
    );

    $followers->each(function ($follower) {
        expect($follower->notifications()->count())->toBe(1);
    });

    assertDatabaseHas('notifications', [
        'notifiable_id' => $followers->first()->id,
        'type' => PostCreatedNotification::class,
    ]);
});
