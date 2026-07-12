<?php

namespace App\Http\Resources;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Comment
 *
 * Used for both comments and replies. `replies` is only present when
 * eager-loaded (top-level comments load theirs; a reply doesn't nest
 * further, since replies are one level deep by design).
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
            'replies' => CommentResource::collection($this->whenLoaded('replies')),
        ];
    }
}
