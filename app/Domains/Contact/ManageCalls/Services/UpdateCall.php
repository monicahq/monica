<?php

namespace App\Domains\Contact\ManageCalls\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Call;
use App\Models\Emotion;
use App\Services\BaseService;
use Carbon\Carbon;

class UpdateCall extends BaseService implements ServiceInterface
{
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
            'call_id' => 'required|integer|exists:calls,id',
            'call_reason_id' => 'nullable|integer|exists:call_reasons,id',
            'called_at' => 'required|date_format:Y-m-d',
            'duration' => 'nullable|integer',
            'type' => 'required|string',
            'answered' => 'nullable|boolean',
            'who_initiated' => 'required|string',
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
     * Update a call.
     *
     * @param  array  $data
     * @return Call
     */
    public function execute(array $data): Call
    {
        $this->data = $data;
        $this->validate();

        $call = Call::where('contact_id', $data['contact_id'])
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
            Emotion::where('account_id', $this->data['account_id'])
                ->where('id', $this->data['emotion_id'])
                ->firstOrFail();
        }
    }
}
