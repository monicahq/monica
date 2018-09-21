<?php

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

class ConversationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'happened_at' => 'required|date',
        ];
    }
}
