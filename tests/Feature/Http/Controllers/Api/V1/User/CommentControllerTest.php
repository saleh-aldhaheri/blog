<?php

use App\Enums\PostStatusEnum;
use App\Enums\RoleEnum;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseMissing;

describe('comment index', function () {
    beforeEach(function () {
        $this->user = CreateUserAs(RoleEnum::USER);
        Sanctum::actingAs($this->user);
    });

    it('returns 200 and cursor-paginated comments with user for a post', function () {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        Comment::factory(4)->create([
            'post_id' => $post->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson(route('api.user.posts.comments.index', $post))
            ->assertOk();

        $items = $response->json('data.data');
        expect($items)->toHaveCount(4)
            ->and(collect($items)->first())->toHaveKeys(['id', 'content', 'user']);
    });

    it('returns 200 and only comments matching search in content', function () {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $needle = 'UniqueSearchMarkerXy9';

        Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $this->user->id,
            'content' => 'Noise one',
        ]);

        Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $this->user->id,
            'content' => "Contains {$needle} here",
        ]);

        $url = route('api.user.posts.comments.index', $post).'?search='.urlencode($needle);

        $response = $this->getJson($url)->assertOk();

        expect($response->json('data.data'))->toHaveCount(1)
            ->and($response->json('data.data.0.content'))->toContain($needle);
    });

    it('respects the limit query parameter', function () {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        Comment::factory(8)->create([
            'post_id' => $post->id,
            'user_id' => $this->user->id,
        ]);

        $url = route('api.user.posts.comments.index', $post).'?limit=3';

        $response = $this->getJson($url)->assertOk();

        expect($response->json('data.data'))->toHaveCount(3);
    });
});

describe('comment store', function () {
    beforeEach(function () {
        $this->user = CreateUserAs(RoleEnum::USER);
        Sanctum::actingAs($this->user);
    });

    it('returns 201 and creates a comment with user when valid', function () {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $body = 'This is a valid comment that meets the minimum length.';

        $response = $this->postJson(route('api.user.posts.comments.store', $post), [
            'comment' => $body,
        ])->assertCreated()
            ->assertJsonPath('message', 'Comment created successfully')
            ->assertJsonPath('data.content', $body)
            ->assertJsonPath('data.user.id', $this->user->id);
    });

    it('returns 422 when comment is too short', function () {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $this->postJson(route('api.user.posts.comments.store', $post), [
            'comment' => 'a',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['comment']);
    });

    it('returns 422 when comment is missing', function () {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $this->postJson(route('api.user.posts.comments.store', $post), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['comment']);
    });
});

describe('comment update', function () {
    beforeEach(function () {
        $this->user = CreateUserAs(RoleEnum::USER);
        Sanctum::actingAs($this->user);
    });

    it('returns 200 and updates the comment when the user is the author', function () {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $this->user->id,
            'content' => 'Original comment text that is long enough for rules.',
        ]);

        $newBody = 'Updated comment body with enough characters to pass validation here.';

        $this->putJson(route('api.user.comments.update', $comment), [
            'comment' => $newBody,
        ])->assertOk()
            ->assertJsonPath('message', 'Comment updated successfully')
            ->assertJsonPath('data.content', $newBody);
    });

    it('returns 422 when comment is invalid', function () {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $this->user->id,
        ]);

        $this->putJson(route('api.user.comments.update', $comment), [
            'comment' => 'x',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['comment']);
    });

    it('returns 403 when another user tries to update the comment', function () {
        $owner = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $owner->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $owner->id,
            'content' => 'Owner comment text with sufficient length here.',
        ]);

        $this->putJson(route('api.user.comments.update', $comment), [
            'comment' => 'Malicious update attempt with enough length to validate.',
        ])->assertForbidden();
    });
});

describe('comment destroy', function () {
    beforeEach(function () {
        $this->user = CreateUserAs(RoleEnum::USER);
        Sanctum::actingAs($this->user);
    });

    it('returns 204 and deletes the comment when the user is the author', function () {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $this->user->id,
        ]);

        $this->deleteJson(route('api.user.comments.destroy', $comment))
            ->assertNoContent();

        assertDatabaseMissing('comments', ['id' => $comment->id]);
    });

    it('returns 403 when another user tries to delete the comment', function () {
        $owner = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $owner->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $owner->id,
        ]);

        $this->deleteJson(route('api.user.comments.destroy', $comment))
            ->assertForbidden();
    });
});

describe('comment routes without authentication', function () {
    it('returns 401 for index when not authenticated', function () {
        $post = Post::factory()->create([
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $this->getJson(route('api.user.posts.comments.index', $post))
            ->assertUnauthorized();
    });

    it('returns 401 for store when not authenticated', function () {
        $post = Post::factory()->create([
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $this->postJson(route('api.user.posts.comments.store', $post), [
            'comment' => 'A comment that is long enough to pass if auth worked.',
        ])->assertUnauthorized();
    });
});
