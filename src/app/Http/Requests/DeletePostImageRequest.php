<?php

namespace App\Http\Requests;

class DeletePostImageRequest extends ApiRequest
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
            'uuid' => 'required|string|uuid',
            // [TODO] image_group_uuidを使った一括削除の実装
            //'image_group_uuid' => 'string|uuid',
        ];
    }
}
