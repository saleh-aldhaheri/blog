<?php

use App\Enums\PostStatusEnum;
use App\Enums\RoleEnum;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $user = CreateUserAs(RoleEnum::USER);
    $this->actingAs($user);
});

describe('user posts', function () {
    it('returns 200 and only published posts when the viewer is not the post owner', function () {
        $owner = User::factory(1)->create()->first();
        Post::factory(10)->create([
            'user_id' => $owner->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        Post::factory(2)->create([
            'user_id' => $owner->id,
            'status' => PostStatusEnum::DRAFT,
        ]);

        $response = $this->getJson(route('api.user.user.posts', $owner->id))
            ->assertStatus(200);

        expect($response->json('data'))->toHaveCount(10)
            ->and(collect($response->json('data'))->pluck('status'))
            ->not->toContain(PostStatusEnum::DRAFT->value);
    });

    it('returns 200 and includes drafts when the viewer is the post owner, respecting the limit', function () {
        $owner = User::factory(1)->create()->first();
        $this->actingAs($owner);

        Post::factory(8)->create([
            'user_id' => $owner->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        Post::factory(20)->create([
            'user_id' => $owner->id,
            'status' => PostStatusEnum::DRAFT,
        ]);

        $response = $this->getJson(route('api.user.user.posts', [$owner->id,  'limit' => 15]))
            ->assertStatus(200);

        expect($response->json('data'))->toHaveCount(15)
            ->and(collect($response->json('data'))->pluck('status'))
            ->toContain(PostStatusEnum::DRAFT->value);
    });

    it('returns 200 and matching posts when search matches the post title', function () {
        $owner = User::factory(1)->create()->first();
        Post::factory(10)->create([
            'user_id' => $owner->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $title = 'new post';

        Post::factory(1)->create([
            'user_id' => $owner->id,
            'title' => $title,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $response = $this->getJson(route('api.user.user.posts', [$owner->id, 'search' => $title]))
            ->assertStatus(200);

        expect($response->json('data'))->toHaveCount(1)
            ->and(collect($response->json('data'))->first()['title'])
            ->toBe($title);
    });
});

describe('show post', function () {
    it('returns 200 with the post, category, and user for a published post', function () {
        $owner = User::factory(1)->create()->first();

        $post = Post::factory(1)->create([
            'user_id' => $owner->id,
            'status' => PostStatusEnum::PUBLISHED,
        ])->first();

        $response = $this->getJson(route('api.user.posts.show', $post->id))
            ->assertStatus(200);

        expect($response->json()['data']['id'])
            ->toBe($post->id)
            ->and($response->json()['data'])->toHaveKeys(['category', 'user']);
    });

    it('returns 403 when a non-owner views another user draft', function () {

        $owner = User::factory(1)->create()->first();

        $post = Post::factory(1)->create([
            'user_id' => $owner->id,
            'status' => PostStatusEnum::DRAFT,
        ])->first();

        $response = $this->getJson(route('api.user.posts.show', $post->id))
            ->assertStatus(403);

        expect($response->json())
            ->not->toHaveKey('data');
    });
});

describe('index posts', function () {
    it('returns 200 with only published posts, not drafts', function (int $limit) {

        $owner = User::factory(1)->create()->first();

        Post::factory(10)->create([
            'user_id' => $owner->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        Post::factory(2)->create([
            'user_id' => $owner->id,
            'status' => PostStatusEnum::DRAFT,
        ]);

        $response = $this->getJson(route('api.user.posts.index'), ['limit' => $limit])
            ->assertStatus(200);

        expect($response->json('data'))->toHaveCount(10)
            ->and(collect($response->json('data'))->pluck('status'))
            ->not->toContain(PostStatusEnum::DRAFT->value);
    })->with([2, 5, 10]);

    it('returns 200 and one post when the search string matches a title', function () {

        $owner = User::factory(1)->create()->first();

        Post::factory(100)->create([
            'user_id' => $owner->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $title = 'new post';

        Post::factory(1)->create([
            'user_id' => $owner->id,
            'title' => $title,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $response = $this->getJson(route('api.user.posts.index', ['search' => $title]))
            ->assertStatus(200);

        expect($response->json('data'))->toHaveCount(1)
            ->and(collect($response->json('data'))->first()['title'])
            ->toBe($title);
    });
});

describe('post store - DTO validation', function () {
    beforeEach(function () {
        $this->user = CreateUserAs(RoleEnum::USER);
        Sanctum::actingAs($this->user);
    });

    it('returns 201 with post data when PostData passes validation', function () {
        $category = Category::factory()->create();

        $title = fake()->realText(80);

        $response = $this->withHeaders(['Accept' => 'application/json'])->post(route('api.user.posts.store'), [
            'title' => $title,
            'categoryId' => $category->id,
            'status' => PostStatusEnum::PUBLISHED->value,
            'thumbnails' => UploadedFile::fake()->image('thumb.jpg'),
            'content' => [
                [
                    'type' => 'text',
                    'order' => 1,
                    'value' => 'Body text block that is long enough to validate correctly.',
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', $title);

        expect($response->json('data'))->toHaveKey('id')
            ->and($response->json('data.content'))->toBeArray();
    });

    it('returns 422 with validation errors when title is shorter than 20 characters', function () {
        $category = Category::factory()->create();

        $response = $this->withHeaders(['Accept' => 'application/json'])->post(route('api.user.posts.store'), [
            'title' => 'short title',
            'categoryId' => $category->id,
            'status' => PostStatusEnum::PUBLISHED->value,
            'thumbnails' => UploadedFile::fake()->image('thumb.jpg'),
            'content' => [
                [
                    'type' => 'text',
                    'order' => 1,
                    'value' => 'Not enough title above but body is fine here.',
                ],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    });

    it('returns 422 when status is not published or draft', function () {
        $category = Category::factory()->create();

        $response = $this->withHeaders(['Accept' => 'application/json'])->post(route('api.user.posts.store'), [
            'title' => fake()->realText(80),
            'categoryId' => $category->id,
            'status' => 'archived',
            'thumbnails' => UploadedFile::fake()->image('thumb.jpg'),
            'content' => [
                [
                    'type' => 'text',
                    'order' => 1,
                    'value' => 'Valid body text for the post content here.',
                ],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    });

    it('returns 422 when a media content block has no file', function () {
        $category = Category::factory()->create();

        $response = $this->withHeaders(['Accept' => 'application/json'])->post(route('api.user.posts.store'), [
            'title' => fake()->realText(80),
            'categoryId' => $category->id,
            'status' => PostStatusEnum::PUBLISHED->value,
            'thumbnails' => UploadedFile::fake()->image('thumb.jpg'),
            'content' => [
                [
                    'type' => 'media',
                    'order' => 1,
                    'value' => null,
                ],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['content.0.file']);
    });

    it('returns 422 when thumbnails is missing', function () {
        $category = Category::factory()->create();

        $response = $this->withHeaders(['Accept' => 'application/json'])->post(route('api.user.posts.store'), [
            'title' => fake()->realText(80),
            'categoryId' => $category->id,
            'status' => PostStatusEnum::PUBLISHED->value,
            'content' => [
                [
                    'type' => 'text',
                    'order' => 1,
                    'value' => 'Content without thumbnail should fail validation now.',
                ],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['thumbnails']);
    });
});

describe('post update - DTO validation', function () {
    beforeEach(function () {
        $this->user = CreateUserAs(RoleEnum::USER);
        Sanctum::actingAs($this->user);
    });

    it('returns 200 when UpdatePostData is valid', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $newCategory = Category::factory()->create();
        $newTitle = fake()->realText(80);

        $response = $this->putJson(route('api.user.posts.update', $post), [
            'title' => $newTitle,
            'categoryId' => $newCategory->id,
            'status' => PostStatusEnum::PUBLISHED->value,
            'content' => null,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', $newTitle)
            ->assertJsonPath('data.category_id', $newCategory->id);
    });

    it('returns 422 when title is present but shorter than 20 characters', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $this->putJson(route('api.user.posts.update', $post), [
            'title' => 'too short',
            'categoryId' => $post->category_id,
            'content' => null,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    });

    it('returns 422 when a content item type is invalid', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $this->putJson(route('api.user.posts.update', $post), [
            'title' => fake()->realText(80),
            'categoryId' => $post->category_id,
            'content' => [
                [
                    'type' => 'invalid_type',
                    'value' => 'Some value text here for the block',
                    'order' => 1,
                    'media' => null,
                ],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['content.0.type']);
    });

    it('returns 403 when the authenticated user cannot update the post', function () {
        $other = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $other->id]);

        $this->putJson(route('api.user.posts.update', $post), [
            'title' => fake()->realText(80),
            'categoryId' => $post->category_id,
            'content' => null,
        ])->assertForbidden();
    });
});

describe('get viewed posts', function () {
    it('returns 401 when the request is unauthenticated', function () {
        auth()->logout();

        $this->getJson(route('api.user.posts.viewed'))
            ->assertUnauthorized();
    });

    it('returns 200 with an empty list when the user has not viewed any posts', function () {
        Post::factory(5)->create([
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $response = $this->getJson(route('api.user.posts.viewed'))
            ->assertOk();

        expect($response->json('data'))->toHaveCount(0);
    });

    it('returns 200 with only published posts the user has viewed', function () {
        $user = auth()->user();

        $viewedPublished = Post::factory()->create([
            'status' => PostStatusEnum::PUBLISHED,
        ]);
        $viewedDraft = Post::factory()->create([
            'status' => PostStatusEnum::DRAFT,
        ]);
        Post::factory()->create([
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $user->viewedPosts()->syncWithoutDetaching([
            $viewedPublished->id,
            $viewedDraft->id,
        ]);

        $response = $this->getJson(route('api.user.posts.viewed'))
            ->assertOk();

        $items = $response->json('data');

        expect($items)->toHaveCount(1)
            ->and($items[0]['id'])->toBe($viewedPublished->id)
            ->and(collect($items)->pluck('status'))
            ->not->toContain(PostStatusEnum::DRAFT->value);
    });

    it('returns 200 and filters by search when the query matches a viewed post title', function () {
        $user = auth()->user();
        $needle = 'ViewedSearchTitle '.uniqid();

        $matching = Post::factory()->create([
            'title' => $needle,
            'status' => PostStatusEnum::PUBLISHED,
        ]);
        $other = Post::factory()->create([
            'title' => 'Other title not matching search',
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $user->viewedPosts()->syncWithoutDetaching([
            $matching->id,
            $other->id,
        ]);

        $response = $this->getJson(route('api.user.posts.viewed', ['search' => $needle]))
            ->assertOk();

        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data')[0]['title'])->toBe($needle);
    });

    it('returns 200 and respects the limit query parameter', function () {
        $user = auth()->user();

        $posts = Post::factory(10)->create([
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $user->viewedPosts()->syncWithoutDetaching($posts->pluck('id')->all());

        $response = $this->getJson(route('api.user.posts.viewed', ['limit' => 4]))
            ->assertOk();

        expect($response->json('data'))->toHaveCount(4);
    });
});
