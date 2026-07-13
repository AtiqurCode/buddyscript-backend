<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with enough fake data to exercise
     * every API endpoint (feed pagination, private-post visibility,
     * comments, replies, and likes on all of the above) and to look like
     * an actually-used feed rather than a handful of placeholder rows.
     */
    public function run(): void
    {
        $testUser = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ]);

        $users = collect([$testUser])->concat(User::factory(29)->create());

        $posts = $users->flatMap(
            fn (User $user) => Post::factory()->for($user)->count(fake()->numberBetween(3, 8))->create()
        );

        $posts->each(function (Post $post) use ($users) {
            $this->attachRandomLikers($post, $users, 0, 15);

            if (! fake()->boolean(80)) {
                return;
            }

            Comment::factory()
                ->for($post)
                ->count(fake()->numberBetween(1, 6))
                ->create(['user_id' => fn () => $users->random()->id])
                ->each(function (Comment $comment) use ($users) {
                    $this->attachRandomLikers($comment, $users, 0, 8);

                    if (! fake()->boolean(60)) {
                        return;
                    }

                    Comment::factory()
                        ->replyTo($comment)
                        ->count(fake()->numberBetween(1, 3))
                        ->create(['user_id' => fn () => $users->random()->id])
                        ->each(fn (Comment $reply) => $this->attachRandomLikers($reply, $users, 0, 6));
                });
        });
    }

    /** Attach a random subset of users (never the author) as likers of a post/comment/reply. */
    private function attachRandomLikers(Post|Comment $likeable, Collection $users, int $min, int $max): void
    {
        $candidates = $users->where('id', '!=', $likeable->user_id);
        $likers = $candidates->random(min(fake()->numberBetween($min, $max), $candidates->count()));
        $likeable->likes()->attach($likers->pluck('id'));
    }
}
