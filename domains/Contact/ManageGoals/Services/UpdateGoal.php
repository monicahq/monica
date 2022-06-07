<?php

namespace App\Contact\ManageGoals\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Goal;
use App\Services\BaseService;
use Carbon\Carbon;

class UpdateGoal extends BaseService implements ServiceInterface
{
    private Goal $goal;

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
            'goal_id' => 'nullable|integer|exists:goals,id',
            'name' => 'nullable|string|max:255',
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
     * Update a goal.
     *
     * @param  array  $data
     * @return Goal
     */
    public function execute(array $data): Goal
    {
        $this->validateRules($data);

        $this->goal = Goal::where('contact_id', $data['contact_id'])
            ->findOrFail($data['goal_id']);

        $this->goal->name = $data['name'];
        $this->goal->save();

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();

        return $this->goal;
    }
}
