<?php

namespace App\Policies;

use App\Enums\PostVisibility;
use App\Models\Post;
use App\Models\User;

/**
 * Only view/create — there's no edit or delete endpoint, so those policy
 * methods would just be dead code nobody calls.
 */
class PostPolicy
{
    /**
     * The list endpoint filters visibility at the query level (see
     * Post::scopeVisibleTo) for performance — this method is the second
     * line of defense for anyone hitting a single post by id directly.
     */
    public function view(User $user, Post $post): bool
    {
        return $post->visibility === PostVisibility::Public || $post->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }
}
