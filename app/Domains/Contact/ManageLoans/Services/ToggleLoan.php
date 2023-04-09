<?php

namespace App\Domains\Contact\ManageLoans\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Loan;
use App\Services\BaseService;
use Carbon\Carbon;

class ToggleLoan extends BaseService implements ServiceInterface
{
    private Loan $loan;

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
            'loan_id' => 'required|integer|exists:loans,id',
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
     * Toggle a loan.
     */
    public function execute(array $data): Loan
    {
        $this->data = $data;
        $this->validate();
        $this->toggle();

        return $this->loan;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->loan = $this->vault->loans()
            ->findOrFail($this->data['loan_id']);
    }

    private function toggle(): void
    {
        $this->loan->settled = ! $this->loan->settled;
        $this->loan->settled_at = Carbon::now();
        $this->loan->save();

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }
}
