<?php

namespace App\Services\Account\Company;

use App\Jobs\AuditLog\LogAccountAudit;
use App\Models\Account\Company;
use App\Models\User\User;
use App\Services\BaseService;
use function Safe\json_encode;

class CreateOrGetCompany extends BaseService
{
    private array $data;
    private ?Company $company;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'author_id' => 'required|integer|exists:users,id',
            'name' => 'required|string|max:255',
            'website' => 'nullable|string|max:255',
            'number_of_employees' => 'nullable|integer',
        ];
    }

    /**
     * Create a company.
     *
     * @param array $data
     * @return Company
     */
    public function execute(array $data): Company
    {
        $this->validate($data);
        $this->data = $data;

        $this->checkIfCompanyAlreadyExists();

        if (! $this->company) {
            $this->createCompany();
            $this->log();
        }

        return $this->company;
    }

    private function checkIfCompanyAlreadyExists(): void
    {
        $this->company = null;

        $existingCompany = Company::where('account_id', $this->data['account_id'])
            ->where('name', $this->data['name'])
            ->first();

        if ($existingCompany) {
            $this->company = $existingCompany;
        }
    }

    private function createCompany(): void
    {
        $this->company = Company::create([
            'account_id' => $this->data['account_id'],
            'name' => $this->data['name'],
            'website' => $this->nullOrValue($this->data, 'website'),
            'number_of_employees' => $this->nullOrValue($this->data, 'number_of_employees'),
        ]);
    }

    private function log(): void
    {
        $author = User::find($this->data['author_id']);

        LogAccountAudit::dispatch([
            'action' => 'company_created',
            'account_id' => $this->data['account_id'],
            'about_contact_id' => null,
            'author_id' => $author->id,
            'author_name' => $author->name,
            'audited_at' => now(),
            'should_appear_on_dashboard' => true,
            'objects' => json_encode([
                'name' => $this->data['name'],
            ]),
        ]);
    }
}
