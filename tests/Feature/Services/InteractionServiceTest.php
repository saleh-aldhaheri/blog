<?php

use App\Enums\InteractionTypeEnum;
use App\Enums\RoleEnum;
use App\Models\Comment;
use App\Models\Interaction;
use App\Models\Post;
use App\Services\InteractionService;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    $this->interactionService = new InteractionService();
});

describe('storeInteraction', function () {
    it('should store new interaction', function () {
        $user = CreateUserAs(RoleEnum::USER);
        $this->actingAs($user);
        $post = Post::factory(1)->create()->first();

        $interaction = $this->interactionService->storeInteraction($post, InteractionTypeEnum::LIKE->value);

        expect($interaction)->toBeInstanceOf(Interaction::class)
            ->and($interaction->user_id)->toBe($user->id)
            ->and($interaction->interactable_id)->toBe($post->id)
            ->and($interaction->action)->toBe(InteractionTypeEnum::LIKE)
            ->and($interaction->interactable_type)->toBe(Post::class);
    });

    it('should update interaction if exists', function () {
        $user = CreateUserAs(RoleEnum::USER);
        $this->actingAs($user);
        $post = Post::factory(1)->create()->first();

        $interaction = Interaction::factory(1)->create();

        $interaction = $this->interactionService->storeInteraction($post, InteractionTypeEnum::LOVE->value);

        expect($interaction)->toBeInstanceOf(Interaction::class)
            ->and($interaction->user_id)->toBe($user->id)
            ->and($interaction->interactable_id)->toBe($post->id)
            ->and($interaction->action)->toBe(InteractionTypeEnum::LOVE)
            ->and($interaction->interactable_type)->toBe(Post::class);
    });

    it('should create interaction of type comment', function () {
        $user = CreateUserAs(RoleEnum::USER);
        $this->actingAs($user);
        $comment = Comment::factory(1)->create()->first();

        $interaction = $this->interactionService->storeInteraction($comment, InteractionTypeEnum::LIKE->value);

        expect($interaction)->toBeInstanceOf(Interaction::class)
            ->and($interaction->user_id)->toBe($user->id)
            ->and($interaction->interactable_id)->toBe($comment->id)
            ->and($interaction->action)->toBe(InteractionTypeEnum::LIKE)
            ->and($interaction->interactable_type)->toBe(Comment::class);
    });
});

describe('deleteInteraction', function () {
    it('should create interaction of type comment', function () {

        $interaction = Interaction::factory(1)->create()->first();

        assertDatabaseHas('interactions', [
            'user_id' =>  $interaction->user_id,
            'interactable_type' => $interaction->interactable_type,
            'interactable_id' => $interaction->interactable_id
        ]);

        $this->interactionService->deleteInteraction($interaction);

        assertDatabaseMissing('interactions', [
            'user_id' =>  $interaction->user_id,
            'interactable_type' => $interaction->interactable_type,
            'interactable_id' => $interaction->interactable_id
        ]);
    });
});
