<?php

use App\Enums\RoleEnum;
use App\Models\Category;

beforeEach(function () {
    $this->user = CreateUserAs(RoleEnum::USER);
});

describe('index', function () {
    it('should return all exiting categories with 200 status code', function () {
        Category::factory(20)->create();
        $this->actingAs($this->user);
        $response = $this->getJson(route('api.user.categories'))
            ->assertOk();
        expect(count($response->json('data')))
            ->toBe(20);
    });

    it('should return 401 when unauthenticated user made a request', function () {

        Category::factory(20)->create();

        $this->getJson(route('api.user.categories'))
            ->assertStatus(401);
    });
});
