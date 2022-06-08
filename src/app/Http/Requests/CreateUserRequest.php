<?php

namespace App\Http\Requests;

use App\Rules\CountryIdRule;

class CreateUserRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name'       => 'required|string|max:45',
            'username'   => 'required|string|max:16|alpha_dash|unique:users,username',
            'country_id' => ['required', 'integer', new CountryIdRule],
        ];
    }
}
