<?php

use App\Enums\RoleEnum;
use App\Models\Comment;
use App\Models\Post;
use App\Services\CommentService;
use Illuminate\Pagination\CursorPaginator;

beforeEach(function () {
    $this->user = CreateUserAs(RoleEnum::USER);
    $this->actingAs($this->user);
    $this->commentService = new CommentService;
});

describe('getComment', function () {
    it('should get Post Comments with respecting the limit', function ($limit) {
        $post = Post::factory(1)->create()->first();
        Comment::factory(20)->create([
            'post_id' => $post->id,
        ]);

        $comments = $this->commentService->getComments(post: $post, limit: $limit);

        expect($comments)
            ->toBeInstanceOf(CursorPaginator::class)
            ->and($comments->items())->toHaveCount($limit);
    })->with([10, 5, 15]);

    it('should search Post Comment by content', function () {
        $post = Post::factory(1)->create()->first();
        Comment::factory(20)->create([
            'post_id' => $post->id,
        ]);

        $content = fake()->text();
        Comment::factory(1)->create([
            'post_id' => $post->id,
            'content' => $content,
        ]);

        $comments = $this->commentService->getComments(post: $post, search: $content);

        expect($comments)
            ->toBeInstanceOf(CursorPaginator::class)
            ->and($comments->items())->toHaveCount(1)
            ->and($comments->first()->content)->toBe($content);
    });
});

describe('storeComment', function () {
    it('should store the content', function () {
        $content = fake()->text();
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
        ])
            ->first();

        $comment = $this->commentService->storeComment($post, $content);

        expect($comment)->not()->toBeNull()
            ->and($post->comments()->first()->id)->toBe($comment->id)
            ->and($this->user->comments()->first()->id)->toBe($comment->id);
    });
});

describe('updateComment', function () {
    it('should update the comment contents', function () {
        $content = fake()->text();

        $post = Post::factory()->create([
            'user_id' => $this->user->id,
        ])
            ->first();

        $comment = Comment::factory(1)->create([
            'post_id' => $post->id,
            'content' => 'my content',
        ])->first();

        $comment = $this->commentService->updateComment($comment, $content);

        expect($comment)->not()->toBeNull()
            ->and($comment->content)->toBe($content);
    });
});
