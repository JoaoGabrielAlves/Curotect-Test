<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class PostController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly PostService $postService
    ) {}

    /**
     * Create a new post.
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        $this->authorize('create', Post::class);

        try {
            $post = $this->postService->createPost($request->validated(), Auth::user());

            return response()->json([
                'data' => new PostResource($post),
                'message' => 'Post created successfully!',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create post',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a post with ETag concurrency control.
     */
    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        $this->authorize('update', $post);

        $validated = $request->validated();
        $etag = $validated['etag'];
        unset($validated['etag']);

        try {
            $updatedPost = $this->postService->updatePost($post, $validated, $etag);

            return response()->json([
                'data' => new PostResource($updatedPost),
                'message' => 'Post updated successfully!',
            ]);
        } catch (InvalidArgumentException $e) {
            // ETag mismatch - concurrency conflict
            return response()->json([
                'message' => 'Concurrency conflict',
                'error' => $e->getMessage(),
                'errors' => [
                    'etag' => [$e->getMessage()],
                ],
            ], 409);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update post',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a post.
     */
    public function destroy(Post $post): JsonResponse
    {
        $this->authorize('delete', $post);

        try {
            $this->postService->deletePost($post);

            return response()->json([
                'message' => 'Post deleted successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete post',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
