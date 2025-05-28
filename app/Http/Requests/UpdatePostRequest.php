<?php

namespace App\Http\Requests;

use App\Models\Post;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $post = $this->route('post');

        // User must be authenticated and own the post
        return $this->user() && $post && $this->user()->id === $post->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'min:3',
                'max:255',
            ],
            'content' => [
                'required',
                'string',
                'min:10',
                'max:65535',
            ],
            'status' => [
                'required',
                'string',
                Rule::in(['draft', 'published', 'archived']),
            ],
            'category' => [
                'nullable',
                'string',
                'max:100',
            ],
            'published_at' => [
                'nullable',
                'date',
                'after_or_equal:now',
            ],
            'etag' => [
                'required',
                'string',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The post title is required.',
            'title.min' => 'The post title must be at least 3 characters.',
            'title.max' => 'The post title cannot exceed 255 characters.',
            'content.required' => 'The post content is required.',
            'content.min' => 'The post content must be at least 10 characters.',
            'content.max' => 'The post content cannot exceed 65,535 characters.',
            'status.required' => 'Please select a post status.',
            'status.in' => 'The selected status is invalid.',
            'category.max' => 'The category cannot exceed 100 characters.',
            'published_at.date' => 'Please provide a valid publication date.',
            'etag.required' => 'ETag is required for concurrency control.',
            'published_at.after_or_equal' => 'The publication date cannot be in the past.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'published_at' => 'publication date',
            'etag' => 'version identifier',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Auto-set published_at if status is published and no date provided
        if ($this->input('status') === 'published' && ! $this->input('published_at')) {
            $this->merge([
                'published_at' => now(),
            ]);
        }

        // Clear published_at if status is not published
        if ($this->input('status') !== 'published') {
            $this->merge([
                'published_at' => null,
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $post = $this->route('post');
            $etag = $this->input('etag');

            if ($post && $etag && ! $post->isEtagValid($etag)) {
                $validator->errors()->add('etag', 'This post has been modified by another user. Please refresh and try again.');
            }
        });
    }
}
