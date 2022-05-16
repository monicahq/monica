<?php

namespace App\Contact\ManageJobInformation\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Company;
use App\Models\Contact;
use App\Services\BaseService;

class UpdateJobInformation extends BaseService implements ServiceInterface
{
    private Company $company;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'vault_id' => 'required|integer|exists:vaults,id',
            'author_id' => 'required|integer|exists:users,id',
            'contact_id' => 'required|integer|exists:contacts,id',
            'company_id' => 'nullable|integer|exists:companies,id',
            'job_position' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     *
     * @return array
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
            'author_must_be_vault_editor',
            'contact_must_belong_to_vault',
        ];
    }

    /**
     * Update job information for the given contact.
     *
     * @param  array  $data
     * @return Contact
     */
    public function execute(array $data): Contact
    {
        $this->validateRules($data);

        if (! is_null($this->valueOrNull($data, 'company_id'))) {
            $this->company = Company::where('vault_id', $data['vault_id'])
                ->findOrFail($data['company_id']);
        }

        $this->contact->company_id = $data['company_id'] ? $this->company->id : null;
        $this->contact->job_position = $this->valueOrNull($data, 'job_position');
        $this->contact->save();

        return $this->contact;
    }
}
