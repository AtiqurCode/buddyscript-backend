<?php

namespace App\Http\Resources;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Post
 *
 * likes_count, comments_count and liked_by_me are expected to already be
 * on the model (withCount/withExists in the controller query) — computing
 * them here per-post instead would mean a query per post in the feed.
 */
class PostResource extends JsonResource
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
            'image_url' => $this->getFirstMediaUrl('image', 'feed') ?: null,
            'visibility' => $this->visibility,
            'created_at' => $this->created_at,
            'likes_count' => $this->likes_count ?? $this->likes()->count(),
            'comments_count' => $this->comments_count ?? $this->topLevelComments()->count(),
            'liked_by_me' => (bool) ($this->liked_by_me ?? false),
            // Preview only (most recent 5), not the full liker list — see
            // the comment on the eager-load in PostController@index.
            'liked_by' => UserResource::collection(
                $this->whenLoaded('likes', fn () => $this->likes->take(5), collect())
            ),
        ];
    }
}
