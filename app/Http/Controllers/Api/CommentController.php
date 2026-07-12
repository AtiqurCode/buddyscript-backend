<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\UserResource;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Top-level comments for a post, newest first, each with its replies
     * eager-loaded. Replies aren't paginated separately — in practice a
     * single comment gets a handful of replies, not thousands, so loading
     * them all alongside their parent is simpler than another round trip.
     */
    public function index(Request $request, Post $post)
    {
        $this->authorize('view', $post);
        $userId = $request->user()->id;

        $likedByMe = fn ($query) => $query->where('user_id', $userId);

        $comments = $post->topLevelComments()
            ->with('user')
            ->withCount('likes')
            ->withExists(['likes as liked_by_me' => $likedByMe])
            ->with(['replies' => function ($query) use ($likedByMe) {
                $query->with('user')
                    ->withCount('likes')
                    ->withExists(['likes as liked_by_me' => $likedByMe])
                    ->oldest('id');
            }])
            ->latest('id')
            ->cursorPaginate(10);

        return CommentResource::collection($comments);
    }

    public function store(StoreCommentRequest $request, Post $post)
    {
        // Commenting on a post you can't see would leak that it exists.
        $this->authorize('view', $post);

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        $comment->load('user');
        $comment->setRelation('replies', collect());
        $comment->likes_count = 0;
        $comment->liked_by_me = false;

        return (new CommentResource($comment))->response()->setStatusCode(201);
    }

    public function storeReply(StoreCommentRequest $request, Comment $comment)
    {
        $this->authorize('view', $comment->post);

        $reply = Comment::create([
            'post_id' => $comment->post_id,
            'parent_id' => $comment->id,
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        $reply->load('user');
        $reply->likes_count = 0;
        $reply->liked_by_me = false;

        return (new CommentResource($reply))->response()->setStatusCode(201);
    }

    /** Same endpoint for a comment or a reply — both rows live in the comments table. */
    public function like(Request $request, Comment $comment)
    {
        $this->authorize('view', $comment->post);

        $comment->likes()->toggle($request->user()->id);

        return response()->json([
            'liked' => $comment->likes()->where('user_id', $request->user()->id)->exists(),
            'likes_count' => $comment->likes()->count(),
        ]);
    }

    public function likes(Request $request, Comment $comment)
    {
        $this->authorize('view', $comment->post);

        return UserResource::collection($comment->likes()->paginate(20));
    }
}
