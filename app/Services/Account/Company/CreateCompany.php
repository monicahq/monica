<?php

namespace App\Services\Account\Company;

use App\Models\User\User;
use App\Services\BaseService;
use function Safe\json_encode;
use App\Models\Account\Company;
use Safe\Exceptions\JsonException;
use App\Jobs\AuditLog\LogAccountAudit;

class CreateCompany extends BaseService
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

        $this->log($data);

        return Company::create([
            'account_id' => $data['account_id'],
            'name' => $data['name'],
            'website' => $this->nullOrValue($data, 'website'),
            'number_of_employees' => $this->nullOrValue($data, 'number_of_employees'),
        ]);
    }

    /**
     * Add an audit log.
     *
     * @param array $data
     * @return void
     * @throws JsonException
     */
    private function log(array $data): void
    {
        $author = User::find($data['author_id']);

        LogAccountAudit::dispatch([
            'action' => 'company_created',
            'account_id' => $data['account_id'],
            'about_contact_id' => null,
            'author_id' => $author->id,
            'author_name' => $author->name,
            'audited_at' => now(),
            'should_appear_on_dashboard' => true,
            'objects' => json_encode([
                'name' => $data['name'],
            ]),
        ]);
    }
}
