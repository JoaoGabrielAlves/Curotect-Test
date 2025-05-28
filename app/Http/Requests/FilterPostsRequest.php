<?php

namespace App\Http\Requests;

use App\DTOs\PostFiltersDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FilterPostsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:draft,published,archived',
            'sort' => 'nullable|string|in:title,created_at,published_at,views_count,user_name',
            'direction' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:5|max:100',
        ];
    }

    public function getFilters(): PostFiltersDTO
    {
        return PostFiltersDTO::fromArray($this->validated());
    }

    public function getFiltersForPublicPosts(): PostFiltersDTO
    {
        $validated = $this->validated();
        $validated['status'] = 'published';

        return PostFiltersDTO::fromArray($validated);
    }
}
