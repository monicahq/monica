<?php

namespace App\Domains\Contact\ManageCalls\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Call;
use App\Services\BaseService;
use Carbon\Carbon;

class CreateCall extends BaseService implements ServiceInterface
{
    private Call $call;

    private array $data;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'contact_id' => 'required|uuid|exists:contacts,id',
            'call_reason_id' => 'nullable|integer|exists:call_reasons,id',
            'called_at' => 'required|date_format:Y-m-d',
            'duration' => 'nullable|integer',
            'description' => 'nullable|string|max:65535',
            'type' => 'required|string',
            'answered' => 'nullable|boolean',
            'who_initiated' => 'required|string',
            'emotion_id' => 'nullable|integer|exists:emotions,id',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
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
     * Create a call.
     */
    public function execute(array $data): Call
    {
        $this->data = $data;
        $this->validate();

        $this->createCall();
        $this->updateLastEditedDate();

        return $this->call;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        if ($this->valueOrNull($this->data, 'emotion_id')) {
            $this->account()->emotions()
                ->findOrFail($this->data['emotion_id']);
        }
    }

    private function createCall(): void
    {
        $this->call = Call::create([
            'contact_id' => $this->data['contact_id'],
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'called_at' => $this->data['called_at'],
            'duration' => $this->valueOrNull($this->data, 'duration'),
            'call_reason_id' => $this->valueOrNull($this->data, 'call_reason_id'),
            'emotion_id' => $this->valueOrNull($this->data, 'emotion_id'),
            'description' => $this->valueOrNull($this->data, 'description'),
            'type' => $this->data['type'],
            'answered' => $this->valueOrTrue($this->data, 'answered'),
            'who_initiated' => $this->data['who_initiated'],
        ]);
    }

    private function updateLastEditedDate(): void
    {
        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }
}
