<?php

namespace Database\Factories;

use App\Enums\InteractionTypeEnum;
use App\Models\Interaction;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Interaction>
 */
class InteractionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'interactable_id' => Post::factory(),
            'interactable_type' => Post::class,
            'action' => InteractionTypeEnum::LIKE->value,
        ];
    }
}
