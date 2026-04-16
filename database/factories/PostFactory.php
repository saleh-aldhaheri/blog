<?php

namespace Database\Factories;

use App\Enums\PostStatusEnum;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => Str::limit(fake()->realText(rand(25, 120)), 255),
            'user_id' => User::factory(),
            'category_id' => fn () => $this->resolveCategoryId(),
            'status' => fake()->randomElement(PostStatusEnum::cases())->value,
            'content' => [],
        ];
    }

    protected function resolveCategoryId(): int
    {
        $id = Category::query()->value('id');
        if ($id !== null) {
            return (int) $id;
        }

        return Category::query()->create([
            'name' => fake()->unique()->sentence(3),
            'slug' => fake()->unique()->slug(),
        ])->id;
    }

    public function withContent(int $itemsCount = 1): Factory
    {
        return $this->afterCreating(function (Post $post) use ($itemsCount) {

            $blocks = [];

            $types = ['heading', 'text'];

            for ($i = 1; $i <= $itemsCount; $i++) {
                $type = Arr::random($types);
                $value = $type === 'text'
                    ? fake()->paragraph()
                    : fake()->sentence(rand(4, 10));

                $blocks[] = [
                    'type' => $type,
                    'value' => $value,
                    'order' => $i,
                ];
            }
            $post->content = $blocks;
            $post->save();
        });
    }

    public function withContentWithMedia(int $itemsCount = 1): Factory
    {
        return $this->afterCreating(function (Post $post) use ($itemsCount) {
            $blocks = [];
            $types = ['heading', 'text', 'media'];

            for ($i = 1; $i <= $itemsCount; $i++) {
                $type = Arr::random($types);

                if ($type === 'media') {
                    $media = $post->addMediaFromUrl('https://picsum.photos/640/480')
                        ->toMediaCollection('post-content');

                    $blocks[] = [
                        'type' => 'media',
                        'order' => $i,
                        'media' => [
                            'id' => $media->id,
                            'url' => $media->getUrl(),
                        ],
                    ];
                } else {
                    $blocks[] = [
                        'type' => $type,
                        'value' => $type === 'text' ? fake()->paragraph() : fake()->sentence(),
                        'order' => $i,
                    ];
                }
            }

            $post->content = $blocks;
            $post->save();
        });
    }

    public function withContentWithOnlyMedia(int $itemsCount = 1): Factory
    {
        return $this->afterCreating(function (Post $post) use ($itemsCount) {
            $blocks = [];

            for ($i = 1; $i <= $itemsCount; $i++) {
                $media = $post->addMediaFromUrl('https://picsum.photos/640/480')
                    ->toMediaCollection('post-content');

                $blocks[] = [
                    'type' => 'media',
                    'order' => $i,
                    'media' => [
                        'id' => $media->id,
                        'url' => $media->getUrl(),
                    ],
                ];
            }

            $post->content = $blocks;
            $post->save();
        });
    }
}
