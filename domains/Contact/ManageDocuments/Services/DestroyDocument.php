<?php

namespace App\Contact\ManageDocuments\Services;

use App\Interfaces\ServiceInterface;
use App\Models\File;
use App\Services\BaseService;
use Carbon\Carbon;

class DestroyDocument extends BaseService implements ServiceInterface
{
    private File $file;

    private array $data;

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
            'file_id' => 'required|integer|exists:files,id',
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
            'contact_must_belong_to_vault',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Destroy a file of the document type.
     *
     * @param  array  $data
     */
    public function execute(array $data): void
    {
        $this->data = $data;
        $this->validate();

        $this->file->delete();

        $this->updateLastEditedDate();
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->file = File::where('contact_id', $this->contact->id)
            ->where('type', File::TYPE_DOCUMENT)
            ->findOrFail($this->data['file_id']);
    }

    private function updateLastEditedDate(): void
    {
        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }
}
