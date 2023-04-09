<?php

namespace App\Domains\Contact\ManageLoans\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Models\Loan;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UpdateLoan extends BaseService implements ServiceInterface
{
    private Loan $loan;

    private Collection $loanersCollection;

    private Collection $loaneesCollection;

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
            'currency_id' => 'nullable|integer|exists:currencies,id',
            'type' => 'required|string|max:255',
            'name' => 'required|string|max:65535',
            'description' => 'nullable|string|max:65535',
            'loaner_ids' => 'required',
            'loanee_ids' => 'required',
            'amount_lent' => 'nullable|integer',
            'loaned_at' => 'nullable|date_format:Y-m-d',
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
     * Update a loan.
     */
    public function execute(array $data): Loan
    {
        $this->data = $data;
        $this->validate();
        $this->update();

        return $this->loan;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->loan = $this->vault->loans()
            ->findOrFail($this->data['loan_id']);

        $this->loanersCollection = collect($this->data['loaner_ids'])
            ->map(fn (string $id): Contact => $this->vault->contacts()->findOrFail($id));

        $this->loaneesCollection = collect($this->data['loanee_ids'])
            ->map(fn (string $id): Contact => $this->vault->contacts()->findOrFail($id));
    }

    private function update(): void
    {
        $this->loan->type = $this->data['type'];
        $this->loan->name = $this->data['name'];
        $this->loan->description = $this->valueOrNull($this->data, 'description');
        $this->loan->amount_lent = $this->valueOrNull($this->data, 'amount_lent');
        $this->loan->loaned_at = $this->valueOrNull($this->data, 'loaned_at');
        $this->loan->currency_id = $this->valueOrNull($this->data, 'currency_id');
        $this->loan->save();

        DB::transaction(function () {
            // remove all the current loaners and loanees
            DB::table('contact_loan')->where('loan_id', $this->loan->id)->delete();

            foreach ($this->loanersCollection as $loaner) {
                foreach ($this->loaneesCollection as $loanee) {
                    $loaner->loansAsLoaner()->syncWithoutDetaching([$this->loan->id => ['loanee_id' => $loanee->id]]);
                }
            }

            foreach ($this->loaneesCollection as $loanee) {
                foreach ($this->loanersCollection as $loaner) {
                    $loanee->loansAsLoanee()->syncWithoutDetaching([$this->loan->id => ['loaner_id' => $loaner->id]]);
                }
            }
        });

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }
}
