<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentUser = Auth::user();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'excerpt' => $this->generateExcerpt(),
            'status' => $this->status,
            'category' => $this->category,
            'views_count' => $this->views_count,
            'comments_count' => $this->comments_count ?? $this->comments()->count(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'published_at' => $this->published_at?->toISOString(),
            'user' => new UserResource($this->whenLoaded('user')),
            'comments' => $this->when(
                $this->relationLoaded('comments'),
                fn () => CommentResource::collection($this->comments)
            ),
            'etag' => $this->e_tag,
            'can_edit' => $currentUser && $currentUser->id === $this->user_id,
            'can_delete' => $currentUser && $currentUser->id === $this->user_id,
            'reading_time' => $this->calculateReadingTime(),
            'is_owner' => $this->when(
                $currentUser && $currentUser->id === $this->user_id,
                true
            ),
        ];
    }

    private function generateExcerpt(int $length = 150): string
    {
        $stripped = strip_tags($this->content);
        if (strlen($stripped) <= $length) {
            return $stripped;
        }

        return substr($stripped, 0, $length).'...';
    }

    private function calculateReadingTime(): int
    {
        $wordCount = str_word_count(strip_tags($this->content));
        $wordsPerMinute = 200;

        return max(1, ceil($wordCount / $wordsPerMinute));
    }
}
