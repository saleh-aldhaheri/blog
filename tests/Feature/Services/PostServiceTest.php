<?php

use App\Data\PostContentData;
use App\Data\PostContentMediaData;
use App\Data\PostData;
use App\Data\UpdatePostContentData;
use App\Data\UpdatePostData;
use App\Enums\PostStatusEnum;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Spatie\LaravelData\DataCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    $this->postService = new PostService;
});

describe('getUserPosts', function () {

    it('should return n user posts', function () {
        $user = User::factory()->create();

        $this->actingAs($user);

        $posts = Post::factory(10)->create([
            'user_id' => $user->id,
        ]);

        $result = $this->postService->getUserPosts($user);

        expect($result->items())->toHaveCount(10)  // ✅ items() gets the actual records
            ->and(collect($result->items())->pluck('id'))
            ->toEqual($posts->pluck('id'));
    });

    it('should return passed number of posts', function (int $limit) {

        $user = User::factory(1)->create()->first();

        $this->actingAs($user);

        Post::factory(10)->create([
            'user_id' => $user->id,
            'status' => PostStatusEnum::PUBLISHED->value,
        ]);

        $result = $this->postService->getUserPosts(user: $user, limit: $limit);

        expect($result->items())->toHaveCount($limit);
    })->with([3, 4, 5]);

    it('should search the post by title', function () {
        $user = User::factory(1)->create()->first();
        $title = 'new post';

        $this->actingAs($user);

        Post::factory(10)->create([
            'user_id' => $user->id,
            'status' => PostStatusEnum::PUBLISHED->value,
        ]);

        Post::factory(1)->create([
            'title' => $title,
            'user_id' => $user->id,
        ]);

        $result = $this->postService->getUserPosts(user: $user, search: $title);

        expect($result->items())->toHaveCount(1)
            ->and($result->first()->title)->toBe($title);
    });

    it('should filter non published posts if the post not belong to the user', function () {
        $user = User::factory(1)->create()->first();
        $owner = User::factory(1)->create()->first();

        $this->actingAs($user);

        Post::factory(10)->create([
            'user_id' => $owner->id,
            'status' => PostStatusEnum::DRAFT->value,
        ]);

        $result = $this->postService->getUserPosts(user: $owner);

        expect($result->items())->toHaveCount(0);
    });
});

describe('getPosts', function () {
    it('returns only published posts with category and user loaded', function () {
        $this->actingAs(User::factory()->create());

        Post::factory(3)->create([
            'status' => PostStatusEnum::PUBLISHED->value,
        ]);
        Post::factory(2)->create([
            'status' => PostStatusEnum::DRAFT->value,
        ]);

        $result = $this->postService->getPosts();

        expect($result->items())->toHaveCount(3);

        $first = $result->first();
        expect($first->relationLoaded('category'))->toBeTrue()
            ->and($first->relationLoaded('user'))->toBeTrue()
            ->and($first->category)->not->toBeNull()
            ->and($first->user)->not->toBeNull();
    });

    it('respects the limit argument', function () {
        $this->actingAs(User::factory()->create());

        Post::factory(10)->create([
            'status' => PostStatusEnum::PUBLISHED->value,
        ]);

        $result = $this->postService->getPosts(limit: 4);

        expect($result->items())->toHaveCount(4);
    });
});

describe('showPost', function () {
    it('loads category and user relations on the post', function () {
        $this->actingAs(User::factory()->create());

        $post = Post::factory()->create([
            'status' => PostStatusEnum::PUBLISHED->value,
        ]);

        $post->unsetRelation('category')->unsetRelation('user');

        $loaded = $this->postService->showPost($post);

        expect($loaded->relationLoaded('category'))->toBeTrue()
            ->and($loaded->relationLoaded('user'))->toBeTrue()
            ->and($loaded->category)->not->toBeNull()
            ->and($loaded->user)->not->toBeNull();
    });
});

describe('store', function () {
    it('should store the post', function () {
        $user = User::factory()->create();
        $this->actingAs($user);
        $blocks = [];
        $types = ['heading', 'text', 'media'];

        for ($i = 1; $i <= 10; $i++) {
            $type = Arr::random($types);
            $value = null;
            $file = null;

            if ($type === 'media') {
                $file = UploadedFile::fake()->image("photo-{$i}.jpg");
            } else {
                $value = fake()->text();
            }

            $blocks[] = new PostContentData($type, $i, $value, $file);
        }

        $postData = new PostData(
            title: fake()->realText(80),
            thumbnails: UploadedFile::fake()->image('thumbnail.jpg'),
            categoryId: Category::factory()->create()->id,
            content: PostContentData::collect($blocks, DataCollection::class),
            status: PostStatusEnum::PUBLISHED->value,
        );

        $post = $this->postService->storePost($postData);

        expect($post)->toBeInstanceOf(Post::class)
            ->and($post->title)->toBe($postData->title)
            ->and($post->content)->toHaveCount(10);
    });

    it('stores draft status and assigns the authenticated user and category', function () {
        $user = User::factory()->create();
        $this->actingAs($user);
        $category = Category::factory()->create();

        $blocks = [
            new PostContentData('heading', 1, fake()->text(), null),
            new PostContentData('text', 2, fake()->text(), null),
        ];

        $postData = new PostData(
            title: fake()->realText(80),
            thumbnails: UploadedFile::fake()->image('thumbnail.jpg'),
            categoryId: $category->id,
            content: PostContentData::collect($blocks, DataCollection::class),
            status: PostStatusEnum::DRAFT->value,
        );

        $post = $this->postService->storePost($postData);

        $post->refresh();

        expect($post->status)->toBe(PostStatusEnum::DRAFT)
            ->and($post->user_id)->toBe($user->id)
            ->and($post->category_id)->toBe($category->id);
    });

    it('attaches the thumbnail to the post-thumbnails media collection', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $blocks = [
            new PostContentData('text', 1, fake()->text(), null),
        ];

        $postData = new PostData(
            title: fake()->realText(80),
            thumbnails: UploadedFile::fake()->image('featured.jpg'),
            categoryId: Category::factory()->create()->id,
            content: PostContentData::collect($blocks, DataCollection::class),
            status: PostStatusEnum::PUBLISHED->value,
        );

        $post = $this->postService->storePost($postData);

        expect($post->getFirstMedia('post-thumbnails'))->not->toBeNull()
            ->and($post->getMedia('post-thumbnails'))->toHaveCount(1);
    });

    it('persists heading text and media blocks with ids urls and order', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $blocks = [
            new PostContentData('heading', 1, 'Section title for the post block', null),
            new PostContentData('text', 2, fake()->paragraph(), null),
            new PostContentData('media', 3, null, UploadedFile::fake()->image('inline.jpg')),
        ];

        $postData = new PostData(
            title: fake()->realText(80),
            thumbnails: UploadedFile::fake()->image('thumb.jpg'),
            categoryId: Category::factory()->create()->id,
            content: PostContentData::collect($blocks, DataCollection::class),
            status: PostStatusEnum::PUBLISHED->value,
        );

        $post = $this->postService->storePost($postData);
        $stored = $post->fresh()->content;

        expect($stored)->toHaveCount(3)
            ->and($stored[0])->toMatchArray([
                'type' => 'heading',
                'order' => 1,
                'value' => 'Section title for the post block',
            ])
            ->and($stored[1]['type'])->toBe('text')
            ->and($stored[1])->toHaveKey('value')
            ->and($stored[2]['type'])->toBe('media')
            ->and($stored[2])->toHaveKey('media')
            ->and($stored[2]['media'])->toHaveKeys(['id', 'url'])
            ->and($stored[2]['order'])->toBe(3);
    });

    it('stores post-content media in the post-content collection', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $blocks = [
            new PostContentData('media', 1, null, UploadedFile::fake()->image('a.jpg')),
            new PostContentData('media', 2, null, UploadedFile::fake()->image('b.jpg')),
        ];

        $postData = new PostData(
            title: fake()->realText(80),
            thumbnails: UploadedFile::fake()->image('thumb.jpg'),
            categoryId: Category::factory()->create()->id,
            content: PostContentData::collect($blocks, DataCollection::class),
            status: PostStatusEnum::PUBLISHED->value,
        );

        $post = $this->postService->storePost($postData);

        expect($post->getMedia('post-content'))->toHaveCount(2);
    });
});

describe('update', function () {
    it('updates title category and status without changing content when content is null', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $originalContent = [
            ['type' => 'text', 'value' => 'Original paragraph stays put.', 'order' => 1],
        ];

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'content' => $originalContent,
        ]);

        $newCategory = Category::factory()->create();

        $update = new UpdatePostData(
            title: fake()->realText(80),
            categoryId: $newCategory->id,
            content: null,
            status: PostStatusEnum::PUBLISHED->value,
        );

        $updated = $this->postService->updatePost($post, $update);

        expect($updated->title)->toBe($update->title)
            ->and($updated->category_id)->toBe($newCategory->id)
            ->and($updated->status)->toBe(PostStatusEnum::PUBLISHED)
            ->and($updated->content)->toBe($originalContent);
    });

    it('replaces content blocks when content is provided', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'content' => [
                ['type' => 'text', 'value' => 'Old', 'order' => 1],
            ],
        ]);

        $incoming = [
            new UpdatePostContentData(
                type: 'heading',
                value: 'New heading that is long enough here',
                order: 1,
                media: null,
            ),
            new UpdatePostContentData(
                type: 'text',
                value: 'Replacement body text for the updated post.',
                order: 2,
                media: null,
            ),
        ];

        $update = new UpdatePostData(
            title: fake()->realText(80),
            categoryId: $post->category_id,
            content: UpdatePostContentData::collect($incoming, DataCollection::class),
            status: PostStatusEnum::DRAFT->value,
        );

        $updated = $this->postService->updatePost($post, $update);

        expect($updated->content)->toHaveCount(2)
            ->and($updated->content[0]['type'])->toBe('heading')
            ->and($updated->content[1]['type'])->toBe('text')
            ->and($updated->content[1]['value'])->toBe('Replacement body text for the updated post.');
    });

    it('deletes media records that are removed from the content payload', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $blocks = [
            new PostContentData('text', 1, fake()->text(), null),
            new PostContentData('media', 2, null, UploadedFile::fake()->image('gone.jpg')),
            new PostContentData('text', 3, fake()->text(), null),
        ];

        $post = $this->postService->storePost(new PostData(
            title: fake()->realText(80),
            thumbnails: UploadedFile::fake()->image('thumb.jpg'),
            categoryId: Category::factory()->create()->id,
            content: PostContentData::collect($blocks, DataCollection::class),
            status: PostStatusEnum::PUBLISHED->value,
        ));

        $removedMediaId = $post->content[1]['media']['id'];
        expect(Media::query()->whereKey($removedMediaId)->exists())->toBeTrue();

        $keepText = $post->content[0];
        $keepText2 = $post->content[2];

        $incoming = [
            new UpdatePostContentData(
                type: $keepText['type'],
                value: $keepText['value'],
                order: 1,
                media: null,
            ),
            new UpdatePostContentData(
                type: $keepText2['type'],
                value: $keepText2['value'],
                order: 2,
                media: null,
            ),
        ];

        $update = new UpdatePostData(
            title: fake()->realText(80),
            categoryId: $post->category_id,
            content: UpdatePostContentData::collect($incoming, DataCollection::class),
            status: PostStatusEnum::PUBLISHED->value,
        );

        $this->postService->updatePost($post->fresh(), $update);

        expect(Media::query()->whereKey($removedMediaId)->exists())->toBeFalse();
    });

    it('replaces an image when newMedia is provided for an existing media block', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $blocks = [
            new PostContentData('media', 1, null, UploadedFile::fake()->image('original.jpg')),
        ];

        $post = $this->postService->storePost(new PostData(
            title: fake()->realText(80),
            thumbnails: UploadedFile::fake()->image('thumb.jpg'),
            categoryId: Category::factory()->create()->id,
            content: PostContentData::collect($blocks, DataCollection::class),
            status: PostStatusEnum::PUBLISHED->value,
        ));

        $oldId = $post->content[0]['media']['id'];
        $oldUrl = $post->content[0]['media']['url'];

        $incoming = [
            new UpdatePostContentData(
                type: 'media',
                value: null,
                order: 1,
                media: new PostContentMediaData(
                    id: $oldId,
                    url: $oldUrl,
                    newMedia: UploadedFile::fake()->image('replacement.jpg'),
                ),
            ),
        ];

        $update = new UpdatePostData(
            title: fake()->realText(80),
            categoryId: $post->category_id,
            content: UpdatePostContentData::collect($incoming, DataCollection::class),
            status: PostStatusEnum::PUBLISHED->value,
        );

        $updated = $this->postService->updatePost($post->fresh(), $update);

        $newId = $updated->content[0]['media']['id'];

        expect($newId)->not->toBe($oldId)
            ->and(Media::query()->whereKey($oldId)->exists())->toBeFalse()
            ->and($updated->content[0]['media']['url'])->not->toBe($oldUrl);
    });

    it('keeps existing media when ids match the payload and newMedia is absent', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $blocks = [
            new PostContentData('media', 1, null, UploadedFile::fake()->image('keep.jpg')),
        ];

        $post = $this->postService->storePost(new PostData(
            title: fake()->realText(80),
            thumbnails: UploadedFile::fake()->image('thumb.jpg'),
            categoryId: Category::factory()->create()->id,
            content: PostContentData::collect($blocks, DataCollection::class),
            status: PostStatusEnum::PUBLISHED->value,
        ));

        $mediaId = $post->content[0]['media']['id'];
        $mediaUrl = $post->content[0]['media']['url'];

        $incoming = [
            new UpdatePostContentData(
                type: 'media',
                value: null,
                order: 1,
                media: new PostContentMediaData(
                    id: $mediaId,
                    url: $mediaUrl,
                ),
            ),
        ];

        $update = new UpdatePostData(
            title: fake()->realText(80),
            categoryId: $post->category_id,
            content: UpdatePostContentData::collect($incoming, DataCollection::class),
            status: PostStatusEnum::PUBLISHED->value,
        );

        $updated = $this->postService->updatePost($post->fresh(), $update);

        expect($updated->content[0]['media']['id'])->toBe($mediaId)
            ->and($updated->content[0]['media']['url'])->toBe($mediaUrl)
            ->and(Media::query()->whereKey($mediaId)->exists())->toBeTrue();
    });
});
