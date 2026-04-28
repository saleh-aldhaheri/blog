<?php

use App\Enums\RoleEnum;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = CreateUserAs(RoleEnum::ADMIN);
    Sanctum::actingAs($this->user);
    User::factory(30)->create();
    User::factory(1)->create([
        'name' => 'saleh',
        'email' =>  'saleh@gmail.com'
    ]);
});


describe('index',  function () {
    it('lists users and allows searching and limiting results', function ($search, $limit) {
        $response = $this->getJson(
            route('api.admin.users.index', [
                'search' => $search,
                'limit'  => $limit,
            ])
        )->assertOk();

        $items = $response->json('data');

        if (filled($search)) {
            expect($items)->toHaveCount(1);
        } elseif (filled($limit)) {
            expect($items)->toHaveCount((int) $limit);
        } else {
            expect($items)->not->toBeEmpty();
        }
    })->with([
        ['', 3],
        ['saleh', 10],
        ['saleh@gmail.com', 10],
        ['', ''],
    ]);

    it('should not list admin user', function () {

        User::factory(1)->create([
            'role' =>  RoleEnum::ADMIN,
            'email' => 'salehahmed@gmail.com'
        ]);

        $response = $this->getJson(
            route('api.admin.users.index', [
                'search' =>  'salehahmed @gmail.com',
            ])
        )->assertOk();

        $items = $response->json('data');

        expect($items)->toHaveCount(0);
    });
});


describe('show', function () {
    it('should show a normal user', function () {
        $user = User::factory()->create([
            'role' => RoleEnum::USER,
        ]);

        $this->getJson(
            route('api.admin.users.show', $user->id)
        )->assertOk()
            ->assertJsonPath('data.id', $user->id);
    });

    it('should not show an admin user', function () {
        $admin = User::factory()->create([
            'role' => RoleEnum::ADMIN,
        ]);

        $this->getJson(
            route('api.admin.users.show', $admin->id)
        )->assertForbidden();
    });
});

describe('destroy', function () {
    it('should delete a normal user', function () {
        $user = User::factory()->create([
            'role' => RoleEnum::USER,
        ]);

        $this->deleteJson(
            route('api.admin.users.destroy', $user->id)
        )
            ->assertNoContent();

        expect(
            User::where('id', $user->id)->exists()
        )->toBeFalse();
    });

    it('should not delete an admin user', function () {
        $admin = User::factory()->create([
            'role' => RoleEnum::ADMIN,
        ]);

        $this->deleteJson(
            route('api.admin.users.destroy', $admin->id)
        )->assertForbidden();

        expect(
            User::where('id', $admin->id)->exists()
        )->toBeTrue();
    });
});
