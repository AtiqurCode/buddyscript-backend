<?php

namespace App\Models;

use App\Enums\PostVisibility;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable(['user_id', 'body', 'visibility'])]
class Post extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected function casts(): array
    {
        return [
            'visibility' => PostVisibility::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** All comments on this post, including replies (post_id is denormalized onto replies too). */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /** Just the top-level comments — replies nest under these in the API response. */
    public function topLevelComments(): HasMany
    {
        return $this->comments()->whereNull('parent_id');
    }

    public function likes(): MorphToMany
    {
        return $this->morphToMany(User::class, 'likeable', 'likes')->withTimestamps();
    }

    /**
     * Public posts, plus the given user's own private ones. This is the
     * actual access-control boundary for private posts — it has to live in
     * the query, not a post-fetch policy check, or a private post would
     * still round-trip to the database (and briefly into memory) for every
     * reader before being filtered out.
     */
    public function scopeVisibleTo(Builder $query, ?int $userId): Builder
    {
        return $query->where(function (Builder $q) use ($userId) {
            $q->where('visibility', PostVisibility::Public);

            if ($userId !== null) {
                $q->orWhere(function (Builder $q) use ($userId) {
                    $q->where('visibility', PostVisibility::Private)
                        ->where('user_id', $userId);
                });
            }
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
    }

    /**
     * The feed lists posts far more often than anyone views one full-size,
     * so it's served this capped, compressed conversion instead of the
     * original upload. Kept non-queued (against the package's queued
     * default) because a newly created post needs its feed image ready
     * the moment the create-post request returns, not whenever a queue
     * worker next picks up the job.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('feed')
            ->fit(Fit::Max, 1200, 1200)
            ->quality(80)
            ->nonQueued();
    }
}
