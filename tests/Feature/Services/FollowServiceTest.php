<?php

use App\Enums\RoleEnum;
use App\Exceptions\BusinessException;
use App\Models\User;
use App\Services\FollowService;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->user = CreateUserAs(RoleEnum::USER);
    $this->actingAs($this->user);
    $this->followService = new FollowService;
});

describe('follow and unfollow', function () {
    it('adds a user to followings on follow', function () {
        $other = User::factory()->create();

        $this->followService->follow($other);

        expect($this->user->fresh()->followings()->whereKey($other->id)->exists())->toBeTrue();
    });

    it('throws when following yourself', function () {
        expect(fn () => $this->followService->follow($this->user))
            ->toThrow(BusinessException::class, 'Cannot follow yourself');
    });

    it('keeps a single pivot row when following the same user twice', function () {
        $other = User::factory()->create();

        $this->followService->follow($other);
        $this->followService->follow($other);

        expect(DB::table('follows')
            ->where('follower_id', $this->user->id)
            ->where('following_id', $other->id)
            ->count())->toBe(1);
    });

    it('removes a user from followings on unfollow', function () {
        $other = User::factory()->create();
        $this->followService->follow($other);

        $this->followService->unfollow($other);

        expect($this->user->fresh()->followings()->whereKey($other->id)->exists())->toBeFalse();
    });

    it('does not error when unfollowing a user that was not followed', function () {
        $other = User::factory()->create();

        $this->followService->unfollow($other);

        expect(DB::table('follows')
            ->where('follower_id', $this->user->id)
            ->where('following_id', $other->id)
            ->count())->toBe(0);
    });
});

describe('listings and pagination', function () {
    it('returns cursor-paginated followings for the auth user', function () {
        $other = User::factory()->create();
        $this->followService->follow($other);

        $result = $this->followService->getFollowings(10);

        expect($result)
            ->toBeInstanceOf(CursorPaginator::class)
            ->and(collect($result->items())->pluck('id'))->toContain($other->id);
    });

    it('returns cursor-paginated followers for the auth user', function () {
        $fan = User::factory()->create();
        $this->actingAs($fan);
        (new FollowService)->follow($this->user);

        $this->actingAs($this->user);
        $result = (new FollowService)->getFollowers(10);

        expect($result)
            ->toBeInstanceOf(CursorPaginator::class)
            ->and(collect($result->items())->pluck('id'))->toContain($fan->id);
    });

    it('limits how many followings are returned per page', function () {
        $others = User::factory()->count(3)->create();
        foreach ($others as $other) {
            $this->followService->follow($other);
        }

        $result = $this->followService->getFollowings(2);

        expect($result->items())->toHaveCount(2);
    });
});
