<?php

namespace App\Services\Family\Family;

use App\Models\Family\Family;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Services\BaseService;

class CreateFamily extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'name' => 'required|string|max:255',
        ];
    }

    /**
     * Create a family.
     *
     * @param array $data
     * @return Family
     */
    public function execute(array $data): Family
    {
        $this->validate($data);

        $contact = Family::create($data);

        return $contact;
    }
}
