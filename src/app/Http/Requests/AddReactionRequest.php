<?php

namespace App\Http\Requests;

use App\Rules\ReactionRule;
use App\Rules\UlidRule;
use Illuminate\Foundation\Http\FormRequest;

class AddReactionRequest extends ApiRequest
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
        'reactable_ulid' => ['required', new UlidRule, /*'exists:posts,ulid'*/],
        'reaction_type' => ['required', new ReactionRule],
        ];
    }
}
