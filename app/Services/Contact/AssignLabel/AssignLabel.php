<?php

namespace App\Services\Contact\AssignLabel;

use Carbon\Carbon;
use App\Models\Label;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use App\Jobs\CreateContactLog;
use App\Interfaces\ServiceInterface;

class AssignLabel extends BaseService implements ServiceInterface
{
    private Label $label;

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
            'label_id' => 'required|integer|exists:labels,id',
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
     * Assign a label to the contact.
     *
     * @param  array  $data
     * @return Label
     */
    public function execute(array $data): Label
    {
        $this->validateRules($data);

        $this->label = Label::where('vault_id', $data['vault_id'])
            ->findOrFail($data['label_id']);

        $this->contact->labels()->syncWithoutDetaching($this->label);

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();

        $this->log();

        return $this->label;
    }

    private function log(): void
    {
        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'label_assigned',
            'objects' => json_encode([
                'contact_id' => $this->contact->id,
                'contact_name' => $this->contact->name,
                'label_name' => $this->label->name,
            ]),
        ]);

        CreateContactLog::dispatch([
            'contact_id' => $this->contact->id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'label_assigned',
            'objects' => json_encode([
                'label_name' => $this->label->name,
            ]),
        ]);
    }
}
