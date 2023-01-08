<?php

namespace App\Domains\Contact\ManageLifeEvents\Services;

use App\Interfaces\ServiceInterface;
use App\Models\LifeEvent;
use App\Services\BaseService;

class DestroyLifeEvent extends BaseService implements ServiceInterface
{
    private LifeEvent $lifeEvent;

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
            'life_event_id' => 'required|integer|exists:life_events,id',
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
        ];
    }

    /**
     * Destroy a life event.
     *
     * @param  array  $data
     */
    public function execute(array $data): void
    {
        $this->data = $data;
        $this->validate();

        $this->lifeEvent->delete();
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->lifeEvent = $this->vault->lifeEvents()
            ->findOrFail($this->data['life_event_id']);
    }
}
