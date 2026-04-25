<?php

use App\Enums\InteractionTypeEnum;
use App\Enums\PostStatusEnum;
use App\Enums\RoleEnum;
use App\Models\Comment;
use App\Models\Interaction;
use App\Models\Post;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

describe('post interactions', function () {
    beforeEach(function () {
        $this->user = CreateUserAs(RoleEnum::USER);
        Sanctum::actingAs($this->user);
    });

    it('returns 201 and stores an interaction when action is valid', function () {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $response = $this->postJson(route('api.user.posts.interactions.store', $post), [
            'action' => InteractionTypeEnum::LIKE->value,
        ])->assertCreated();

        $response->assertJsonPath('data.user_id', $this->user->id)
            ->assertJsonPath('data.interactable_id', $post->id)
            ->assertJsonPath('data.interactable_type', Post::class)
            ->assertJsonPath('data.action', InteractionTypeEnum::LIKE->value);

        assertDatabaseHas('interactions', [
            'user_id' => $this->user->id,
            'interactable_type' => Post::class,
            'interactable_id' => $post->id,
            'action' => InteractionTypeEnum::LIKE->value,
        ]);
    });

    it('replaces the existing interaction when the same user posts again', function () {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $this->postJson(route('api.user.posts.interactions.store', $post), [
            'action' => InteractionTypeEnum::LIKE->value,
        ])->assertCreated();

        $this->postJson(route('api.user.posts.interactions.store', $post), [
            'action' => InteractionTypeEnum::LOVE->value,
        ])->assertCreated();

        assertDatabaseCount('interactions', 1);

        assertDatabaseHas('interactions', [
            'user_id' => $this->user->id,
            'interactable_id' => $post->id,
            'interactable_type' => Post::class,
            'action' => InteractionTypeEnum::LOVE->value,
        ]);
    });

    it('returns 422 when action is not a valid enum value', function () {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $this->postJson(route('api.user.posts.interactions.store', $post), [
            'action' => 'not-a-reaction',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['action']);
    });

    it('returns 422 when action is missing', function () {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $this->postJson(route('api.user.posts.interactions.store', $post), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['action']);
    });

    it('returns 204 and deletes the interaction when the user owns it', function () {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $interaction = Interaction::factory()->create([
            'user_id' => $this->user->id,
            'interactable_type' => Post::class,
            'interactable_id' => $post->id,
            'action' => InteractionTypeEnum::LIKE->value,
        ]);

        $this->deleteJson(route('api.user.posts.interactions.destroy', [
            'post' => $post,
            'interaction' => $interaction,
        ]))->assertNoContent();

        assertDatabaseMissing('interactions', ['id' => $interaction->id]);
    });

    it('returns 403 when another user tries to delete an interaction', function () {
        $owner = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $owner->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $interaction = Interaction::factory()->create([
            'user_id' => $owner->id,
            'interactable_type' => Post::class,
            'interactable_id' => $post->id,
            'action' => InteractionTypeEnum::LIKE->value,
        ]);

        $this->deleteJson(route('api.user.posts.interactions.destroy', [
            'post' => $post,
            'interaction' => $interaction,
        ]))->assertForbidden();

        assertDatabaseHas('interactions', ['id' => $interaction->id]);
    });
});

describe('comment interactions', function () {
    beforeEach(function () {
        $this->user = CreateUserAs(RoleEnum::USER);
        Sanctum::actingAs($this->user);
    });

    it('returns 201 and stores an interaction when action is valid', function () {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->postJson(route('api.user.comments.interactions.store', $comment), [
            'action' => InteractionTypeEnum::WOW->value,
        ])->assertCreated();

        $response->assertJsonPath('data.user_id', $this->user->id)
            ->assertJsonPath('data.interactable_id', $comment->id)
            ->assertJsonPath('data.interactable_type', Comment::class)
            ->assertJsonPath('data.action', InteractionTypeEnum::WOW->value);

        assertDatabaseHas('interactions', [
            'user_id' => $this->user->id,
            'interactable_type' => Comment::class,
            'interactable_id' => $comment->id,
            'action' => InteractionTypeEnum::WOW->value,
        ]);
    });

    it('returns 422 when action is invalid', function () {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $this->user->id,
        ]);

        $this->postJson(route('api.user.comments.interactions.store', $comment), [
            'action' => 'invalid',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['action']);
    });

    it('returns 204 and deletes the interaction when the user owns it', function () {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $this->user->id,
        ]);

        $interaction = Interaction::factory()->create([
            'user_id' => $this->user->id,
            'interactable_type' => Comment::class,
            'interactable_id' => $comment->id,
            'action' => InteractionTypeEnum::LIKE->value,
        ]);

        $this->deleteJson(route('api.user.comments.interactions.destroy', [
            'comment' => $comment,
            'interaction' => $interaction,
        ]))->assertNoContent();

        assertDatabaseMissing('interactions', ['id' => $interaction->id]);
    });

    it('returns 403 when another user tries to delete a comment interaction', function () {
        $owner = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $owner->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $owner->id,
        ]);

        $interaction = Interaction::factory()->create([
            'user_id' => $owner->id,
            'interactable_type' => Comment::class,
            'interactable_id' => $comment->id,
            'action' => InteractionTypeEnum::LIKE->value,
        ]);

        $this->deleteJson(route('api.user.comments.interactions.destroy', [
            'comment' => $comment,
            'interaction' => $interaction,
        ]))->assertForbidden();

        assertDatabaseHas('interactions', ['id' => $interaction->id]);
    });
});

describe('interaction routes without authentication', function () {
    it('returns 401 when storing a post interaction without authentication', function () {
        $post = Post::factory()->create([
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $this->postJson(route('api.user.posts.interactions.store', $post), [
            'action' => InteractionTypeEnum::LIKE->value,
        ])->assertUnauthorized();
    });

    it('returns 401 when deleting a post interaction without authentication', function () {
        $user = CreateUserAs(RoleEnum::USER);
        $post = Post::factory()->create([
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $interaction = Interaction::factory()->create([
            'user_id' => $user->id,
            'interactable_type' => Post::class,
            'interactable_id' => $post->id,
            'action' => InteractionTypeEnum::LIKE->value,
        ]);

        $this->deleteJson(route('api.user.posts.interactions.destroy', [
            'post' => $post,
            'interaction' => $interaction,
        ]))->assertUnauthorized();
    });

    it('returns 401 when storing a comment interaction without authentication', function () {
        $post = Post::factory()->create([
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $comment = Comment::factory()->create([
            'post_id' => $post->id,
        ]);

        $this->postJson(route('api.user.comments.interactions.store', $comment), [
            'action' => InteractionTypeEnum::LIKE->value,
        ])->assertUnauthorized();
    });
});
