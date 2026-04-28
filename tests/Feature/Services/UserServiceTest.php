<?php

use App\Enums\RoleEnum;
use App\Models\User;
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
                    fn($user) => $user->role === RoleEnum::ADMIN->value
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
