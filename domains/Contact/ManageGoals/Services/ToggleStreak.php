<?php

namespace App\Contact\ManageGoals\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Goal;
use App\Models\Streak;
use App\Services\BaseService;
use Carbon\Carbon;

class ToggleStreak extends BaseService implements ServiceInterface
{
    private Goal $goal;
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
            'goal_id' => 'nullable|integer|exists:goals,id',
            'happened_at' => 'required|date_format:Y-m-d',
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
     * Log a streak for a given goal.
     *
     * @param  array  $data
     * @return void
     */
    public function execute(array $data): void
    {
        $this->data = $data;

        $this->validate();

        $entry = $this->goal->streaks()
            ->where('goal_id', $this->goal->id)
            ->whereDate('happened_at', $this->data['happened_at'])
            ->first();

        if ($entry) {
            $this->destroyStreak($entry);
        } else {
            $this->createStreak();
        }

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->goal = Goal::where('contact_id', $this->data['contact_id'])
            ->findOrFail($this->data['goal_id']);
    }

    private function destroyStreak(Streak $streak): void
    {
        $streak->delete();
    }

    private function createStreak(): void
    {
        Streak::create([
            'goal_id' => $this->goal->id,
            'happened_at' => $this->data['happened_at'],
        ]);
    }
}
