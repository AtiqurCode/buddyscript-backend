<?php

namespace App\Http\Resources;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Comment
 *
 * Used for both comments and replies. `replies_count` is always the number
 * of direct replies (0 for a reply itself, since replies are one level deep
 * by design) — the replies themselves are never embedded here, only
 * fetched on demand via GET /comments/{comment}/replies.
 */
class CommentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'author' => new UserResource($this->whenLoaded('user')),
            'body' => $this->body,
            'created_at' => $this->created_at,
            'likes_count' => $this->likes_count ?? $this->likes()->count(),
            'liked_by_me' => (bool) ($this->liked_by_me ?? false),
            'replies_count' => $this->replies_count ?? 0,
        ];
    }
}
