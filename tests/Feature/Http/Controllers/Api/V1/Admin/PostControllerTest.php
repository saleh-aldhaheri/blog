<?php

use App\Enums\PostStatusEnum;
use App\Enums\RoleEnum;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\UploadedFile;

use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    $this->user = CreateUserAs(RoleEnum::USER);
    $admin = CreateUserAs(RoleEnum::ADMIN);
    $this->actingAs($admin);
});

describe('show post', function () {
    it('returns 200 with the post, category, and user for post', function () {

        $post = Post::factory(1)->create([
            'user_id' => $this->user->id,
            'status' => PostStatusEnum::PUBLISHED,
        ])->first();

        $response = $this->getJson(route('api.admin.posts.show', $post->id))
            ->assertStatus(200);

        expect($response->json()['data']['id'])
            ->toBe($post->id)
            ->and($response->json()['data'])->toHaveKeys(['category', 'user']);
    });
});

describe('index posts', function () {
    it('returns 200 with all requested posts', function (int $limit) {

        Post::factory(5)->create([
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        Post::factory(5)->create([
            'status' => PostStatusEnum::DRAFT,
        ]);

        $response = $this->getJson(route('api.admin.posts.index', ['limit' => $limit]))
            ->assertStatus(200);

        expect($response->json('data'))->toHaveCount($limit);

        if ($limit > 5) {
            expect(collect($response->json('data'))->pluck('status'))
                ->toContain(PostStatusEnum::DRAFT->value);
        }
    })->with([2, 5, 10]);

    it('returns 200 and one post when the search string matches a title', function () {

        Post::factory(100)->create([
            'user_id' => $this->user->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $title = 'new post';

        Post::factory(1)->create([
            'user_id' => $this->user->id,
            'title' => $title,
            'status' => PostStatusEnum::PUBLISHED,
        ]);

        $response = $this->getJson(route('api.admin.posts.index', ['search' => $title]))
            ->assertStatus(200);

        expect($response->json('data'))->toHaveCount(1)
            ->and(collect($response->json('data'))->first()['title'])
            ->toBe($title);
    });
});

describe('post update - DTO validation', function () {
    it('returns 200 when UpdatePostData is valid', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $newCategory = Category::factory()->create();
        $newTitle = fake()->realText(80);

        $response = $this->putJson(route('api.admin.posts.update', $post), [
            'title' => $newTitle,
            'categoryId' => $newCategory->id,
            'status' => PostStatusEnum::PUBLISHED->value,
            'content' => null,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', $newTitle)
            ->assertJsonPath('data.category_id', $newCategory->id);
    });

    it('preserves title and status when they are omitted (only sent fields are updated)', function () {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'This original title is long enough to stay',
            'status' => PostStatusEnum::PUBLISHED,
        ]);
        $newCategory = Category::factory()->create();

        $this->putJson(route('api.admin.posts.update', $post), [
            'categoryId' => $newCategory->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'This original title is long enough to stay')
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.category_id', $newCategory->id);
    });

    it('returns 422 when title is present but shorter than 5 characters', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $this->putJson(route('api.admin.posts.update', $post), [
            'title' => 'ab',
            'categoryId' => $post->category_id,
            'content' => null,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    });

    it('returns 422 when a content item type is invalid', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $this->putJson(route('api.admin.posts.update', $post), [
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

    it('returns 200 and replaces the thumbnail when thumbnails file is sent', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $post->addMedia(UploadedFile::fake()->image('old-thumb.jpg'))
            ->toMediaCollection('post-thumbnails');
        $oldId = $post->getFirstMedia('post-thumbnails')?->id;

        $this->put(route('api.admin.posts.update', $post), [
            'categoryId' => $post->category_id,
            'thumbnails' => UploadedFile::fake()->image('new-thumb.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['thumbnail']]);

        $post->refresh();
        expect($post->getFirstMedia('post-thumbnails')?->id)->not->toBe($oldId);
    });
});

describe('delete post', function () {
    it('return 204 and delete the post', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id])->first();

        $this->deleteJson(route('api.admin.posts.destroy', $post->id));

        assertDatabaseMissing('posts', [
            'id' => $post->id,
        ]);
    });
});
