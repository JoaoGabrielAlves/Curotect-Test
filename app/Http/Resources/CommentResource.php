<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class CommentResource extends JsonResource
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
            'content' => $this->content,
            'status' => $this->status,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'user' => new UserResource($this->whenLoaded('user')),
            'post_id' => $this->post_id,
            'parent_id' => $this->parent_id,
            'replies_count' => $this->replies_count ?? $this->replies()->count(),
            'replies' => $this->when(
                $this->relationLoaded('replies'),
                fn () => CommentResource::collection($this->replies)
            ),
            'can_edit' => $currentUser && $currentUser->id === $this->user_id,
            'can_delete' => $currentUser && $currentUser->id === $this->user_id,
            'is_author' => $currentUser && $currentUser->id === $this->user_id,
        ];
    }
}
