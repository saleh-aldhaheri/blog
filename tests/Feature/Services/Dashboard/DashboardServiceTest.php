<?php

use App\Models\Comment;
use App\Models\Interaction;
use App\Models\Post;
use App\Models\User;
use App\Services\Dashboard\DashboardService;

it('returns full dashboard structure with all widgets', function () {

    $users = User::factory(2)->create();

    $posts = Post::factory(3)->create([
        'user_id' => $users[0]->id,
    ]);

    Comment::factory(4)->create([
        'post_id' => $posts[0]->id,
        'user_id' => $users[0]->id,
    ]);

    Interaction::factory(1)->create([
        'user_id' => $users[0]->id,
        'interactable_id' => $posts[0]->id,
        'interactable_type' => Post::class,
    ]);

    $result = app(DashboardService::class)();

    expect($result)->toHaveKeys([
        'stats',
        'contents',
        'engagements',
        'analytics',
    ]);

    expect($result['stats'])->toMatchArray([
        'users' => 2,
        'posts' => 3,
        'comments' => 4,
        'interactions' => 1,
    ]);

    expect($result['contents'])->toHaveKeys([
        'post_per_category',
        'recent_posts',
        'top_liked_posts',
    ]);

    expect($result['analytics'])->toHaveKeys([
        'user_growth',
        'post_growth',
    ]);

    expect($result['engagements'])->toBeArray();
});
