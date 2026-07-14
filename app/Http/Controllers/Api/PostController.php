<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * Newest-first feed: public posts plus the viewer's own private ones.
     * Cursor-paginated rather than page-numbered — offset pagination gets
     * slower the deeper you page into a large table, cursor pagination
     * doesn't.
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $posts = Post::query()
            ->visibleTo($userId)
            ->with('user')
            // Preview of who liked, embedded with the feed so the avatar
            // stack renders without a follow-up request per post. Not
            // capped at the query level — Eloquent applies a relation's
            // ->limit() across the whole eager-load batch, not per post —
            // so PostResource takes only the first 5 after load instead.
            ->with(['likes' => fn ($query) => $query->orderByPivot('id', 'desc')])
            // Just the latest top-level comment, not the thread — see
            // Post::latestComment(). Replies never come along with the
            // feed at all, only their count.
            ->with(['latestComment' => function ($query) use ($userId) {
                $query->with('user')
                    ->withCount('replies')
                    ->withExists(['likes as liked_by_me' => fn ($q) => $q->where('user_id', $userId)]);
            }])
            ->withCount(['likes', 'topLevelComments as comments_count'])
            ->withExists(['likes as liked_by_me' => fn ($query) => $query->where('user_id', $userId)])
            ->latest('id')
            ->cursorPaginate(15);

        return PostResource::collection($posts);
    }

    public function store(StorePostRequest $request)
    {
        $this->authorize('create', Post::class);

        $post = $request->user()->posts()->create([
            'body' => $request->validated('body'),
            'visibility' => $request->validated('visibility'),
        ]);

        if ($request->hasFile('image')) {
            $post->addMediaFromRequest('image')->toMediaCollection('image');
        }

        $post->load('user')->loadCount(['likes', 'topLevelComments as comments_count']);
        $post->setRelation('likes', collect());
        $post->setRelation('latestComment', null); // brand new post, no comments yet
        $post->liked_by_me = false; // can't have liked your own post before it existed

        return (new PostResource($post))->response()->setStatusCode(201);
    }

    public function like(Request $request, Post $post)
    {
        $this->authorize('view', $post);

        $post->likes()->toggle($request->user()->id);

        return response()->json([
            'liked' => $post->likes()->where('user_id', $request->user()->id)->exists(),
            'likes_count' => $post->likes()->count(),
        ]);
    }

    /** Full "who liked" list, paginated — the feed response only embeds a 5-liker preview. */
    public function likes(Request $request, Post $post)
    {
        $this->authorize('view', $post);

        return UserResource::collection($post->likes()->paginate(20));
    }
}
