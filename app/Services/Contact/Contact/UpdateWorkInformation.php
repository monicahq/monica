<?php

namespace App\Services\Contact\Contact;

use App\Models\User\User;
use App\Services\BaseService;
use function Safe\json_encode;
use App\Models\Contact\Contact;
use App\Jobs\AuditLog\LogAccountAudit;
use Illuminate\Validation\ValidationException;
use App\Services\Account\Company\CreateOrGetCompany;

class UpdateWorkInformation extends BaseService
{
    private array $data;
    private Contact $contact;

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
            'contact_id' => 'required|integer|exists:contacts,id',
            'job' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
        ];
    }

    /**
     * Update a contact.
     *
     * @param array $data
     * @return Contact
     */
    public function execute(array $data): Contact
    {
        $this->data = $data;
        $this->validateData();

        if (is_null($this->nullOrValue($this->data, 'company'))) {
            $this->resetCompany();
        } else {
            $this->assignCompany();
        }

        $this->contact->job = empty($data['job']) ? null : $data['job'];

        $this->contact->save();
        $this->log();

        return $this->contact->refresh();
    }

    private function validateData(): void
    {
        $this->validate($this->data);

        /* @var Contact */
        $this->contact = Contact::where('account_id', $this->data['account_id'])
            ->findOrFail($this->data['contact_id']);

        if ($this->contact->is_partial) {
            throw ValidationException::withMessages([
                'contact_id' => 'The contact can\'t be a partial contact',
            ]);
        }
    }

    private function assignCompany(): void
    {
        $company = app(CreateOrGetCompany::class)->execute([
            'account_id' => $this->data['account_id'],
            'author_id' => $this->data['author_id'],
            'name' => $this->data['company'],
            'website' => null,
            'number_of_employees' => null,
        ]);

        $this->contact->company_id = $company->id;
    }

    private function resetCompany(): void
    {
        $this->contact->company_id = null;
    }

    private function log(): void
    {
        $author = User::find($this->data['author_id']);

        LogAccountAudit::dispatch([
            'action' => 'contact_work_updated',
            'account_id' => $author->account_id,
            'about_contact_id' => $this->contact->id,
            'author_id' => $author->id,
            'author_name' => $author->name,
            'audited_at' => now(),
            'should_appear_on_dashboard' => true,
            'objects' => json_encode([
                'contact_name' => $this->contact->name,
                'contact_id' => $this->contact->id,
            ]),
        ]);
    }
}
