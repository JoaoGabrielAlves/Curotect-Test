<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Services\CommentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Throwable;

class CommentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CommentService $commentService
    ) {}

    /**
     * Store a newly created comment in storage.
     *
     * @throws Throwable
     */
    public function store(StoreCommentRequest $request, Post $post): RedirectResponse
    {
        $this->authorize('create', [Comment::class, $post]);

        $data = $request->validated();
        $user = Auth::user();

        $comment = $this->commentService->createComment($data, $post, $user);

        return redirect()->route('posts.show', $post)
            ->with('success', 'Comment added successfully!')
            ->with('new_comment_id', $comment->id);
    }

    /**
     * Update the specified comment in storage.
     *
     * @throws Throwable
     */
    public function update(StoreCommentRequest $request, Comment $comment): RedirectResponse
    {
        $this->authorize('update', $comment);

        $data = $request->validated();

        $comment = $this->commentService->updateComment($comment, $data);

        return redirect()->route('posts.show', $comment->post)->with('success', 'Comment updated successfully!');
    }

    /**
     * Remove the specified comment from storage.
     *
     * @throws Throwable
     */
    public function destroy(Comment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        $post = $comment->post;
        $this->commentService->deleteComment($comment);

        return redirect()->route('posts.show', $post)->with('success', 'Comment deleted successfully!');
    }
}
