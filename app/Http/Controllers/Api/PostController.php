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

    /** Who liked this post — the "who liked" requirement, on demand rather than embedded in every feed row. */
    public function likes(Request $request, Post $post)
    {
        $this->authorize('view', $post);

        return UserResource::collection($post->likes()->paginate(20));
    }
}
