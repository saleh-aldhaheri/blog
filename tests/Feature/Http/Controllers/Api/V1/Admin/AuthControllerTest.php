<?php

use App\Enums\RoleEnum;

beforeEach(function () {
    $this->admin = CreateUserAs(RoleEnum::ADMIN);
});

describe('login', function () {
    it('logs in an admin and returns token and user data', function () {
        $response = $this->postJson(route('api.admin.login'), [
            'email' => $this->admin->email,
            'password' => 'password',
        ])->assertOk();

        expect($response->json())->toHaveKeys([
            'message',
            'data' => [
                'token',
                'user',
            ],
        ]);
    });

    it('returns 401 when the account does not exist', function () {
        $response = $this->postJson(route('api.admin.login'), [
            'email' => 'newEmail@gmail.com',
            'password' => 'password',
        ])->assertStatus(401);

        expect($response->json())->toHaveKeys([
            'message',
            'errors',
        ]);
    });

    it('does not allow a normal user to log in as admin', function () {
        $user = CreateUserAs(RoleEnum::USER);

        $response = $this->postJson(route('api.admin.login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertStatus(401);

        expect($response->json())->toHaveKeys([
            'message',
            'errors',
        ]);
    });

    it('returns 401 for wrong credentials', function (mixed $email, string $password) {
        $response = $this->postJson(route('api.admin.login'), [
            'email' => $email($this->admin),
            'password' => $password,
        ])->assertStatus(401);

        expect($response->json())->toHaveKeys([
            'message',
            'errors',
        ]);
    })->with([
        ['email' => fn ($user = null) => $user->email, 'password' => 'wrong password'],
        ['email' => fn ($user = null) => 'wrongEmail@gmail.com', 'password' => 'password'],
    ]);
});

describe('logout', function () {
    it('revokes the current access token and returns 204', function () {
        $token = $this->postJson(route('api.admin.login'), [
            'email' => $this->admin->email,
            'password' => 'password',
        ])->assertOk()->json('data.token');

        $this->withToken($token)
            ->postJson(route('api.admin.logout'))
            ->assertNoContent();
    });

    it('does not allow a normal user to call admin logout', function () {
        $user = CreateUserAs(RoleEnum::USER);

        $token = $this->postJson(route('api.user.login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()->json('data.token');

        $this->withToken($token)
            ->postJson(route('api.admin.logout'))
            ->assertForbidden();
    });
});
