<?php

namespace App\Contact\ManageLabels\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactFeedItem;
use App\Models\Label;
use App\Services\BaseService;
use Carbon\Carbon;

class RemoveLabel extends BaseService implements ServiceInterface
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
     * Remove a label from the contact.
     *
     * @param  array  $data
     * @return Label
     */
    public function execute(array $data): Label
    {
        $this->validateRules($data);

        $this->label = Label::where('vault_id', $data['vault_id'])
            ->findOrFail($data['label_id']);

        $this->contact->labels()->detach($this->label);

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();

        $this->createFeedItem();

        return $this->label;
    }

    private function createFeedItem(): void
    {
        ContactFeedItem::create([
            'author_id' => $this->author->id,
            'contact_id' => $this->contact->id,
            'action' => ContactFeedItem::ACTION_LABEL_REMOVED,
            'description' => $this->label->name,
        ]);
    }
}
