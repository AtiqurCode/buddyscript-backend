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
     * Top-level comments for a post, newest first. Replies aren't included
     * here — only their count — the same way the feed embeds just one
     * comment. A comment's replies load separately via
     * GET /comments/{comment}/replies once the user actually expands them.
     */
    public function index(Request $request, Post $post)
    {
        $this->authorize('view', $post);
        $userId = $request->user()->id;

        $comments = $post->topLevelComments()
            ->with('user')
            ->withCount(['likes', 'replies'])
            ->withExists(['likes as liked_by_me' => fn ($query) => $query->where('user_id', $userId)])
            ->latest('id')
            ->cursorPaginate(10);

        return CommentResource::collection($comments);
    }

    /** A comment's replies, oldest first, paginated — loaded on demand when the user expands them. */
    public function replies(Request $request, Comment $comment)
    {
        $this->authorize('view', $comment->post);
        $userId = $request->user()->id;

        $replies = $comment->replies()
            ->with('user')
            ->withCount('likes')
            ->withExists(['likes as liked_by_me' => fn ($query) => $query->where('user_id', $userId)])
            ->oldest('id')
            ->cursorPaginate(10);

        return CommentResource::collection($replies);
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
        $comment->likes_count = 0;
        $comment->liked_by_me = false;
        $comment->replies_count = 0;

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
        $reply->replies_count = 0; // replies are one level deep by design

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
