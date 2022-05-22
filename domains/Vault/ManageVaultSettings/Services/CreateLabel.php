<?php

namespace App\Vault\ManageVaultSettings\Services;

use App\Interfaces\ServiceInterface;
use App\Jobs\CreateAuditLog;
use App\Models\Label;
use App\Services\BaseService;
use Illuminate\Support\Str;

class CreateLabel extends BaseService implements ServiceInterface
{
    private array $data;
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
            'author_id' => 'required|integer|exists:users,id',
            'vault_id' => 'required|integer|exists:vaults,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:65535',
            'bg_color' => 'string|max:255',
            'text_color' => 'string|max:255',
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
            'author_must_be_vault_editor',
            'vault_must_belong_to_account',
        ];
    }

    /**
     * Create a label.
     *
     * @param  array  $data
     * @return Label
     */
    public function execute(array $data): Label
    {
        $this->validateRules($data);

        $this->label = Label::create([
            'vault_id' => $data['vault_id'],
            'name' => $data['name'],
            'description' => $this->valueOrNull($data, 'description'),
            'slug' => Str::slug($data['name'], '-'),
            'bg_color' => $data['bg_color'],
            'text_color' => $data['text_color'],
        ]);

        $this->log();

        return $this->label;
    }

    private function log(): void
    {
        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'label_created',
            'objects' => json_encode([
                'label_name' => $this->label->name,
            ]),
        ])->onQueue('low');
    }
}
