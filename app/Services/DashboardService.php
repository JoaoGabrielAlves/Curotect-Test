<?php

namespace App\Services;

use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;

readonly class DashboardService
{
    /**
     * Get welcome page data.
     */
    public function getWelcomeData(): array
    {
        $recentPosts = Post::with(['user:id,name'])
            ->where('status', 'published')
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        $stats = [
            'totalPosts' => Post::where('status', 'published')->count(),
            'totalUsers' => User::count(),
            'totalViews' => Post::where('status', 'published')->sum('views_count'),
        ];

        return [
            'recentPosts' => PostResource::collection($recentPosts)->toArray(request()),
            'stats' => $stats,
        ];
    }

    /**
     * Get dashboard data for authenticated user.
     */
    public function getDashboardData(User $user): array
    {
        return [
            'userStats' => $this->getUserStats($user),
            'recentPosts' => $this->getUserRecentPosts($user),
            'systemStats' => $this->getSystemStats(),
        ];
    }

    /**
     * Get user statistics.
     */
    public function getUserStats(User $user): array
    {
        return [
            'totalPosts' => Post::where('user_id', $user->id)->count(),
            'publishedPosts' => Post::where('user_id', $user->id)->where('status', 'published')->count(),
            'draftPosts' => Post::where('user_id', $user->id)->where('status', 'draft')->count(),
            'totalViews' => Post::where('user_id', $user->id)->sum('views_count'),
            'totalComments' => DB::table('comments')
                ->join('posts', 'comments.post_id', '=', 'posts.id')
                ->where('posts.user_id', $user->id)
                ->count(),
        ];
    }

    /**
     * Get user's recent posts.
     */
    public function getUserRecentPosts(User $user): array
    {
        $recentPosts = Post::where('user_id', $user->id)
            ->with(['user:id,name'])
            ->withCount('comments')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'status', 'views_count', 'created_at', 'user_id']);

        return PostResource::collection($recentPosts)->toArray(request());
    }

    /**
     * Get system statistics.
     */
    public function getSystemStats(): array
    {
        return [
            'totalUsers' => User::count(),
            'totalPosts' => Post::count(),
            'totalViews' => Post::sum('views_count'),
            'postsThisMonth' => Post::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];
    }
}
