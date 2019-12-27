<?php

namespace App\Services\Family\Family;

use App\Models\Family\Family;
use App\Services\BaseService;
use App\Models\Contact\Contact;

class AttachContactToFamily extends BaseService
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
            'contacts' => 'required|array',
        ];
    }

    /**
     * Attach or detach contacts to a family.
     *
     * @param array $data
     * @return Family
     */
    public function execute(array $data) : Family
    {
        $this->validate($data);

        $family = Family::where('account_id', $data['account_id'])
            ->findOrFail($data['family_id']);

        $this->attach($data, $family);

        return $family;
    }

    /**
     * Create the association.
     *
     * @param array $data
     * @param Family $family
     * @return void
     */
    private function attach(array $data, Family $family)
    {
        // reset current associations
        $family->contacts()->sync([]);

        foreach ($data['contacts'] as $contactId) {
            Contact::where('account_id', $data['account_id'])
                ->findOrFail($contactId);

            $family->contacts()->syncWithoutDetaching([$contactId]);
        }
    }
}
