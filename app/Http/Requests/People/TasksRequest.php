<?php

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

class TasksRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        if ($this->method() === 'PATCH') {
            return [];
        }

        return [
            'title' => 'required|string',
            'description' => 'nullable',
            'completed' => 'required|boolean',
        ];
    }
}
