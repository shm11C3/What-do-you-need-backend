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
            'category_uuid' => 'required|uuid|exists:post_categories,uuid',
            'title' => 'required|string|max:45',
            'content' => 'required|string|max:4096',
            'is_draft' => 'required|boolean',
            'is_publish' => 'required|boolean',
        ];
    }
}
