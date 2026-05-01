<?php

use App\Enums\RoleEnum;
use App\Models\User;
use App\Notifications\NewFollowerNotification;
use App\Services\ProfileService;
use App\Services\UserService;
use Illuminate\Pagination\CursorPaginator;

beforeEach(function () {
    $this->admin = CreateUserAs(RoleEnum::ADMIN);
    $this->actingAs($this->admin);

    $this->userService = new UserService(new ProfileService);
});

describe('getUsers', function () {
    it('returns cursor paginated non-admin users', function () {
        User::factory(5)->create([
            'role' => RoleEnum::USER,
        ]);

        User::factory(2)->create([
            'role' => RoleEnum::ADMIN,
        ]);

        $result = $this->userService->getUsers(limit: 3);

        expect($result)
            ->toBeInstanceOf(CursorPaginator::class)
            ->and($result->items())->toHaveCount(3)
            ->and(
                collect($result->items())->contains(
                    fn ($user) => $user->role === RoleEnum::ADMIN->value
                )
            )->toBeFalse();
    });

    it('searches users by name', function () {
        User::factory()->create([
            'name' => 'Saleh Ahmed',
            'role' => RoleEnum::USER,
        ]);

        User::factory()->create([
            'name' => 'John Doe',
            'role' => RoleEnum::USER,
        ]);

        $result = $this->userService->getUsers('Saleh');

        expect($result->items())
            ->toHaveCount(1)
            ->and($result->items()[0]->name)
            ->toBe('Saleh Ahmed');
    });

    it('searches users by email', function () {
        User::factory()->create([
            'email' => 'saleh@gmail.com',
            'role' => RoleEnum::USER,
        ]);

        User::factory()->create([
            'email' => 'john@example.com',
            'role' => RoleEnum::USER,
        ]);

        $result = $this->userService->getUsers('saleh@gmail.com');

        expect($result->items())
            ->toHaveCount(1)
            ->and($result->items()[0]->email)
            ->toBe('saleh@gmail.com');
    });

    it('respects the limit', function () {
        User::factory(10)->create([
            'role' => RoleEnum::USER,
        ]);

        $result = $this->userService->getUsers(limit: 4);

        expect($result->items())->toHaveCount(4);
    });
});

describe('getUser', function () {
    it('returns user profile data', function () {
        $user = User::factory()->create([
            'role' => RoleEnum::USER,
        ]);

        $result = $this->userService->getUser($user);

        expect($result)
            ->toBeInstanceOf(User::class)
            ->and($result->id)->toBe($user->id);
    });
});

describe('deleteUser', function () {
    it('deletes the user', function () {
        $user = User::factory()->create([
            'role' => RoleEnum::USER,
        ]);

        $this->userService->deleteUser($user);

        expect(
            User::where('id', $user->id)->exists()
        )->toBeFalse();
    });
});

describe('get followers notifications', function () {
    beforeEach(function () {
        $this->user = CreateUserAs(RoleEnum::USER);
        $this->actingAs($this->user);
    });

    it('should store the notifications in the database', function () {
        $followers = User::factory(10)->create();

        $followers->each(function ($follower) {
            $this->user->notify(new NewFollowerNotification($follower));
        });

        $notifications = $this->userService->getFollowingNotifications();
        expect($notifications->items())->toHaveCount(10);
    });

    it('should not return a read notifications', function () {
        $followers = User::factory(10)->create();

        $followers->each(function ($follower) {
            $this->user->notify(new NewFollowerNotification($follower));
        });

        expect($this->user->notifications)->toHaveCount(10);

        $this->user->notifications()->limit(5)->update([
            'read_at' => now(),
        ]);

        $notifications = $this->userService->getFollowingNotifications();
        expect($notifications->items())->toHaveCount(5);
    });
});

describe('mark notifications as read', function () {

    beforeEach(function () {
        $this->user = CreateUserAs(RoleEnum::USER);
        $this->actingAs($this->user);
    });

    it('should mark the notification as read', function () {

        $follower = CreateUserAs(RoleEnum::USER);

        $this->user->notify(new NewFollowerNotification($follower));

        $notification = $this->user->notifications()->first();
        expect($notification->read_at)->toBeNull();

        $this->userService->markNotificationAsRead($notification);

        $notification->refresh();

        expect($notification->read_at)->not->toBeNull();
    });
});
