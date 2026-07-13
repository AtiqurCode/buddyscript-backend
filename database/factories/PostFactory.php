<?php

namespace Database\Factories;

use App\Enums\PostVisibility;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'user_id' => User::factory(),
            'body' => fake()->boolean(70)
                ? fake()->realText(fake()->numberBetween(40, 220))
                : implode(' ', fake()->sentences(fake()->numberBetween(1, 3))),
            // Feed is mostly public; private posts only show up for their author.
            'visibility' => fake()->boolean(85) ? PostVisibility::Public : PostVisibility::Private,
        ];
    }

    public function public(): static
    {
        return $this->state(['visibility' => PostVisibility::Public]);
    }

    public function private(): static
    {
        return $this->state(['visibility' => PostVisibility::Private]);
    }
}
