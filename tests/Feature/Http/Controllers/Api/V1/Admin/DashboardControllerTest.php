<?php

use App\Enums\RoleEnum;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->admin = CreateUserAs(RoleEnum::ADMIN);
    Sanctum::actingAs($this->admin);
});

it('return status code 200 and with correct dashboard structure', function () {
    $response = $this->getJson(route('api.admin.dashboard'))
        ->assertOk();
    expect($response->json())->toHaveKeys([
        'stats',
        'contents',
        'engagements',
        'analytics',
    ]);
});

it('prevent non admin user from make request to the dashboard', function () {
    $user = CreateUserAs(RoleEnum::USER);
    Sanctum::actingAs($user);
    $this->getJson(route('api.admin.dashboard'))
        ->assertForbidden();
});
