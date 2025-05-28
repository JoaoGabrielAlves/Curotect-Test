<?php

namespace App\Http\Middleware;

use App\Models\Post;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePostOwnership
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $post = $request->route('post');

        if (! $post instanceof Post) {
            abort(404);
        }

        $user = Auth::user();

        if (! $user || $post->user_id !== $user->id) {
            abort(403, 'You do not have permission to access this post.');
        }

        return $next($request);
    }
}
