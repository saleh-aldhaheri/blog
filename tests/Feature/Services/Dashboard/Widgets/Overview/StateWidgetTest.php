<?php

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Interaction;
use App\Services\Dashboard\Widgets\Overview\StateWidget;


it('returns correct system state counts', function () {

    $users = User::factory(2)->create();

    $posts = Post::factory(3)->create([
        'user_id' => $users[0]->id,
    ]);

    Comment::factory(4)->create([
        'post_id' => $posts[0]->id,
        'user_id' => $users[0]->id
    ]);


    foreach ($users as $user) {
        Interaction::factory(1)->create([
            'user_id' => $user->id,
            'interactable_id' => $posts[0]->id,
            'interactable_type' => Post::class
        ]);
    }

    $result = app(StateWidget::class)();

    expect($result)->toMatchArray([
        'users' => 2,
        'posts' => 3,
        'comments' => 4,
        'interactions' => 2,
    ]);
});
