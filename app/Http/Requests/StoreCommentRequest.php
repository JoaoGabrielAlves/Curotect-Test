<?php

namespace App\Http\Requests;

use App\Models\Comment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'content' => 'required|string|min:3|max:1000',
            'parent_id' => [
                'nullable',
                'integer',
                'exists:comments,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $parentComment = Comment::find($value);
                        $currentPost = request()->route('post');

                        if ($parentComment && $parentComment->post_id !== $currentPost->id) {
                            $fail('The parent comment must belong to the same post.');
                        }
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Comment content is required.',
            'content.min' => 'Comment must be at least 3 characters long.',
            'content.max' => 'Comment cannot exceed 1000 characters.',
            'parent_id.exists' => 'The parent comment does not exist.',
        ];
    }
}
