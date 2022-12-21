<?php

namespace App\Http\Requests;

use App\Rules\UlidRule;

class UploadPostImageRequest extends ApiRequest
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
            'image' => 'required|image|max:1024|dimensions:max_width=1920,max_height=1080',
            'image_group_uuid' => 'string|uuid',
        ];
    }
}
