<?php

use App\Enums\RoleEnum;
use App\Models\User;
use App\Services\FollowService;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

describe('follow listings', function () {
    beforeEach(function () {
        $this->user = CreateUserAs(RoleEnum::USER);
        Sanctum::actingAs($this->user);
    });

    it('returns 200 and cursor data for the following list', function () {
        $other = User::factory()->create();
        (new FollowService)->follow($other);

        $response = $this->getJson(route('api.user.follow.following'))
            ->assertOk();

        $items = $response->json('data');
        expect($items)->toBeArray()
            ->and($items)->not->toBeEmpty()
            ->and(collect($items)->pluck('id'))->toContain($other->id);
    });

    it('returns 200 and cursor data for the followers list', function () {
        $fan = User::factory()->create();
        Sanctum::actingAs($fan);
        (new FollowService)->follow($this->user);

        Sanctum::actingAs($this->user);

        $response = $this->getJson(route('api.user.follow.followers'))
            ->assertOk();

        $items = $response->json('data');
        expect($items)->toBeArray()
            ->and($items)->not->toBeEmpty()
            ->and(collect($items)->pluck('id'))->toContain($fan->id);
    });

    it('passes the limit query to the service via followings', function () {
        $others = User::factory()->count(3)->create();
        $service = new FollowService;
        foreach ($others as $other) {
            $service->follow($other);
        }

        $response = $this->getJson(route('api.user.follow.following', ['limit' => 2]))
            ->assertOk();

        expect($response->json('data'))->toHaveCount(2);
    });
});

describe('follow and unfollow actions', function () {
    beforeEach(function () {
        $this->user = CreateUserAs(RoleEnum::USER);
        Sanctum::actingAs($this->user);
    });

    it('returns 201 and creates a follow row', function () {
        $other = User::factory()->create();

        $this->putJson(route('api.user.follow.follow', $other))
            ->assertNoContent(201);

        assertDatabaseHas('follows', [
            'follower_id' => $this->user->id,
            'following_id' => $other->id,
        ]);
    });

    it('returns 204 and removes the follow row on unfollow', function () {
        $other = User::factory()->create();
        (new FollowService)->follow($other);

        $this->putJson(route('api.user.follow.unfollow', $other))
            ->assertNoContent();

        assertDatabaseMissing('follows', [
            'follower_id' => $this->user->id,
            'following_id' => $other->id,
        ]);
    });

    it('returns 422 when the authenticated user tries to follow themselves', function () {
        $this->putJson(route('api.user.follow.follow', $this->user))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Cannot follow yourself');
    });
});

describe('follow routes without authentication', function () {
    it('returns 401 for following list', function () {
        $this->getJson(route('api.user.follow.following'))->assertUnauthorized();
    });

    it('returns 401 for followers list', function () {
        $this->getJson(route('api.user.follow.followers'))->assertUnauthorized();
    });

    it('returns 401 for follow', function () {
        $other = User::factory()->create();

        $this->putJson(route('api.user.follow.follow', $other))->assertUnauthorized();
    });

    it('returns 401 for unfollow', function () {
        $other = User::factory()->create();

        $this->putJson(route('api.user.follow.unfollow', $other))->assertUnauthorized();
    });
});
