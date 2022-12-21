<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'category_uuid' => 'nullable|exists:post_categories,uuid',
            'title' => 'nullable|string|max:45',
            'content' => 'nullable|string|max:4096',
            'is_draft' => 'required|boolean',
            'is_publish' => 'required|boolean',
            'image_group_uuid' => 'string|uuid',
        ];
    }
}
