<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'initials' => $this->generateInitials(),
            'avatar_url' => $this->generateAvatarUrl(),
        ];
    }

    private function generateInitials(): string
    {
        $words = explode(' ', trim($this->name));
        $initials = '';

        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= strtoupper($word[0]);
        }

        return $initials ?: 'U';
    }

    private function generateAvatarUrl(): string
    {
        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=random';
    }
}
