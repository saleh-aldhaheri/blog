<?php

use App\Models\Category;
use App\Enums\RoleEnum;
use App\Models\Post;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    $this->admin = CreateUserAs(RoleEnum::ADMIN);
    Sanctum::actingAs($this->admin);

    Category::factory(20)->create();

    Category::factory()->create([
        'name' => 'Programming',
    ]);
});

describe('index', function () {

    it('lists categories and allows searching and limiting results', function ($search, $limit) {

        $response = $this->getJson(
            route('api.admin.categories.index', [
                'search' => $search,
                'limit' => $limit,
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
        ['Programming', 10],
        ['', ''],
    ]);
});

describe('store', function () {

    it('creates a new category', function () {

        $response = $this->postJson(
            route('api.admin.categories.store'),
            [
                'name' => 'DevOps',
            ]
        )->assertStatus(201);

        expect(Category::where('name', 'DevOps')->exists())
            ->toBeTrue();

        expect($response->json('data.name'))
            ->toBe('DevOps');
    });
});

describe('show', function () {

    it('shows a category', function () {

        $category = Category::factory()->create([
            'name' => 'Testing',
        ]);

        $this->getJson(
            route('api.admin.categories.show', $category->id)
        )
            ->assertOk()
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.name', 'Testing');
    });
});

describe('update', function () {

    it('updates category name', function () {

        $category = Category::factory()->create([
            'name' => 'Old Name',
        ]);

        $this->putJson(
            route('api.admin.categories.update', $category->id),
            [
                'name' => 'New Name',
            ]
        )->assertStatus(200);

        expect($category->fresh()->name)
            ->toBe('New Name');
    });
});

describe('destroy', function () {

    it('deletes a category without posts', function () {

        $category = Category::factory()->create();

        $this->deleteJson(
            route('api.admin.categories.destroy', $category->id)
        )
            ->assertNoContent();

        assertDatabaseMissing('categories', [
            'id' =>  $category->id
        ]);
    });

    it('prevent deleting a category with posts', function () {

        $category = Category::factory()->create();

        Post::factory(1)->create([
            'category_id' => $category->id
        ]);

        $this->deleteJson(
            route('api.admin.categories.destroy', $category->id)
        )
            ->assertStatus(422);

        assertDatabaseHas('categories', [
            'id' => $category->id
        ]);
    });
});
