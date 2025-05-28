<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterPostsRequest;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Throwable;

class PostController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly PostService $postService
    ) {}

    /**
     * Display a listing of all published posts (public view).
     */
    public function index(FilterPostsRequest $request): Response
    {
        $filters = $request->getFiltersForPublicPosts();
        $posts = $this->postService->getPublicPosts($request->validated(), $filters->perPage);
        $categories = $this->postService->getCategories();

        return Inertia::render('Posts/Index', [
            'posts' => PostResource::collection($posts),
            'categories' => $categories,
            'filters' => $filters->toArray(),
            'showActions' => false,
            'showStatusFilter' => false,
        ]);
    }

    /**
     * Display current user's posts with full management capabilities.
     */
    public function myPosts(FilterPostsRequest $request): Response
    {
        $this->authorize('viewAny', Post::class);

        $filters = $request->getFilters();
        $user = Auth::user();
        $posts = $this->postService->getUserPosts($user, $request->validated(), $filters->perPage);

        $categories = $this->postService->getCategories();

        return Inertia::render('Posts/MyPosts', [
            'posts' => PostResource::collection($posts),
            'categories' => $categories,
            'filters' => $filters->toArray(),
            'showActions' => true,
            'showStatusFilter' => true,
        ]);
    }

    /**
     * Display the specified post with comments.
     */
    public function show(Post $post): Response
    {
        $this->authorize('view', $post);

        $post = $this->postService->getPostForViewing($post->id, Auth::user());

        if (! $post) {
            abort(404);
        }

        return Inertia::render('Posts/Show', [
            'post' => new PostResource($post),
        ]);
    }

    /**
     * Show the form for creating a new post.
     */
    public function create(): Response
    {
        $this->authorize('create', Post::class);

        $categories = $this->postService->getCategories();

        return Inertia::render('Posts/Create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created post in storage.
     *
     * @throws Throwable
     */
    public function store(StorePostRequest $request): RedirectResponse
    {
        $this->authorize('create', Post::class);

        $post = $this->postService->createPost($request->validated(), Auth::user());

        return to_route('posts.show', $post)
            ->with('success', 'Post created successfully!');
    }

    /**
     * Show the form for editing the specified post.
     */
    public function edit(Post $post): Response
    {
        $this->authorize('update', $post);

        $post->load('user:id,name,email');

        $categories = $this->postService->getCategories();

        return Inertia::render('Posts/Edit', [
            'post' => new PostResource($post),
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified post in storage.
     *
     * @throws Throwable
     */
    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $this->authorize('update', $post);

        $validated = $request->validated();
        $etag = $validated['etag'];
        unset($validated['etag']);

        try {
            $post = $this->postService->updatePost($post, $validated, $etag);

            return to_route('posts.show', $post)
                ->with('success', 'Post updated successfully!');
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['etag' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified post from storage.
     *
     * @throws Throwable
     */
    public function destroy(Post $post): RedirectResponse
    {
        $this->authorize('delete', $post);

        $this->postService->deletePost($post);

        return to_route('posts.index')
            ->with('success', 'Post deleted successfully!');
    }
}
