<?php

use App\Enums\RoleEnum;
use App\Jobs\DistributePostCreatedNotificationJob;
use App\Models\Post;
use App\Models\User;
use App\Notifications\PostCreatedNotification;
use Illuminate\Support\Facades\Notification;

it('sends post created notification to all followers', function () {
    Notification::fake();

    $author = CreateUserAs(RoleEnum::USER);

    $followers = User::factory(20)->create();

    $post = Post::factory()->create([
        'user_id' => $author->id,
    ]);

    $author->followers()->attach($followers->pluck('id'));

    DistributePostCreatedNotificationJob::dispatchSync($author, $post);

    Notification::assertSentTo(
        $followers,
        PostCreatedNotification::class,
        function ($notification, $channels) use ($author, $post) {
            return $notification->author->id === $author->id
                && $notification->post->id === $post->id;
        }
    );
});
