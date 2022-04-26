<?php

namespace App\Contact\ManageLoans\Services;

use Carbon\Carbon;
use App\Models\Loan;
use App\Services\BaseService;
use App\Interfaces\ServiceInterface;

class ToggleLoan extends BaseService implements ServiceInterface
{
    private Loan $loan;
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
            'loan_id' => 'required|integer|exists:loans,id',
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
     * Toggle a loan.
     *
     * @param  array  $data
     * @return Loan
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

        $this->loan = Loan::where('vault_id', $this->data['vault_id'])
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
