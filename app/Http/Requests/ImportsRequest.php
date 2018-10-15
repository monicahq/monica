<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportsRequest extends FormRequest
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
        return [
            'vcard' => 'required|max:'.config('monica.max_upload_size').'|mimes:vcf,vcard',
        ];
    }
}
