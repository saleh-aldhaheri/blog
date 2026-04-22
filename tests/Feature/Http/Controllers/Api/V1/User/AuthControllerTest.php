<?php

use App\Enums\RoleEnum;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->user = CreateUserAs(RoleEnum::USER);
});

describe('login', function () {
    it('should login the user and return the token with the user data', function () {

        $response = $this->postJson(route('api.user.login'), [
            'email' => $this->user->email,
            'password' => 'password',
        ])
            ->assertOk();

        expect($response->json())->toHaveKeys([
            'message',
            'data' => [
                'token',
                'user',
            ],
        ]);
    });

    it('should return 401 if the user not exist', function () {

        $response = $this->postJson(route('api.user.login'), [
            'email' => 'newEmail@gmail.com',
            'password' => 'password',
        ])
            ->assertStatus(401);

        expect($response->json())->toHaveKeys([
            'message',
            'errors',
        ]);
    });

    it('should not allowed admin to login as user', function () {

        $admin = CreateUserAs(RoleEnum::ADMIN);

        $response = $this->postJson(route('api.user.login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])
            ->assertStatus(401);

        expect($response->json())->toHaveKeys([
            'message',
            'errors',
        ]);
    });

    it('should not allowed for wrong credentials', function (mixed $email, string $password) {
        $response = $this->postJson(route('api.user.login'), [
            'email' => $email($this->user),
            'password' => $password,
        ])
            ->assertStatus(401);

        expect($response->json())->toHaveKeys([
            'message',
            'errors',
        ]);
    })->with([
        ['email' => fn ($user = null) => $user->email, 'password' => 'wrong password'],
        ['email' => fn ($user = null) => 'wrongEmail@gmail.com', 'password' => 'password'],
    ]);
});

describe('register', function () {
    it('should register new user', function (array $payload) {
        $response = $this->postJson(route('api.user.register'), $payload)
            ->assertStatus(201);

        expect($response->json('data'))
            ->toHaveKeys(['id', 'name', 'email', 'role'])
            ->and($response->json('data.email'))->toBe($payload['email']);

        assertDatabaseHas('users', [
            'email' => $payload['email'],
            'role' => RoleEnum::USER->value,
        ]);
    })->with([[[
        'name' => fake()->name(),
        'password' => 'password',
        'email' => fake()->unique()->safeEmail(),
        'password_confirmation' => 'password',
    ]]]);

    it('should validate the payload correctly', function (array $payload) {
        $response = $this->postJson(route('api.user.register'), $payload)
            ->assertStatus(422);
    })->with([[
        [
            'password' => 'password',
            'email' => fake()->unique()->safeEmail(),
            'password_confirmation' => 'password',
        ],
        [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password_confirmation' => 'password',
        ],
        [
            'name' => fake()->name(),
            'password' => 'password1',
            'email' => fake()->unique()->safeEmail(),
            'password_confirmation' => 'password',
        ],
        [
            'name' => fake()->name(),
            'password' => 'password',
            'email' => 'adfasdfsaf',
            'password_confirmation' => 'password',
        ],
    ]]);
});

describe('logout', function () {
    it('revokes the current access token and returns 204', function () {
        $response = $this->postJson(route('api.user.login'), [
            'email' => $this->user->email,
            'password' => 'password',
        ])->assertStatus(200);

        $this->withToken($response->json()['data']['token'])
            ->postJson(route('api.user.logout'))
            ->assertNoContent();
    });

    it('should not allowed admin to revoke the token', function () {

        $admin = CreateUserAs(RoleEnum::ADMIN);

        $response = $this->postJson(route('api.admin.login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertStatus(200);

        $this->withToken($response->json()['data']['token'])
            ->postJson(route('api.user.logout'))
            ->assertForbidden();
    });
});
