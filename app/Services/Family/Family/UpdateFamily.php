<?php

namespace App\Services\Family\Family;

use App\Models\Contact\Tag;
use Illuminate\Support\Str;
use App\Helpers\LocaleHelper;
use App\Models\Family\Family;
use App\Services\BaseService;

class UpdateFamily extends BaseService
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
            'family_id' => 'required|integer|exists:families,id',
            'name' => 'required|string|max:255',
        ];
    }

    /**
     * Update a family.
     *
     * @param array $data
     * @return Family
     */
    public function execute(array $data): Family
    {
        $this->validate($data);

        $family = Family::where('account_id', $data['account_id'])
            ->findOrFail($data['family_id']);

        $family->name = $data['name'];
        $family->save();

        return $family;
    }
}
