<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'parent_id' => null,
            'user_id' => User::factory(),
            'body' => implode(' ', fake()->sentences(fake()->numberBetween(1, 2))),
        ];
    }

    /** A reply nested under an existing top-level comment. */
    public function replyTo(Comment $comment): static
    {
        return $this->state([
            'post_id' => $comment->post_id,
            'parent_id' => $comment->id,
        ]);
    }
}
