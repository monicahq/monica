<?php

namespace App\Domains\Contact\ManageCalls\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Call;
use App\Services\BaseService;
use Carbon\Carbon;

class UpdateCall extends BaseService implements ServiceInterface
{
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
            'call_id' => 'required|integer|exists:calls,id',
            'call_reason_id' => 'nullable|integer|exists:call_reasons,id',
            'called_at' => 'required|date_format:Y-m-d',
            'duration' => 'nullable|integer',
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
            'contact_must_belong_to_vault',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Update a call.
     */
    public function execute(array $data): Call
    {
        $this->data = $data;
        $this->validate();

        $call = $this->contact->calls()
            ->findOrFail($data['call_id']);

        $call->called_at = $data['called_at'];
        $call->duration = $this->valueOrNull($data, 'duration');
        $call->call_reason_id = $this->valueOrNull($data, 'call_reason_id');
        $call->emotion_id = $this->valueOrNull($data, 'emotion_id');
        $call->type = $data['type'];
        $call->answered = $this->valueOrTrue($data, 'answered');
        $call->who_initiated = $data['who_initiated'];
        $call->save();

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();

        return $call;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        if ($this->valueOrNull($this->data, 'emotion_id')) {
            $this->account()->emotions()
                ->findOrFail($this->data['emotion_id']);
        }
    }
}
