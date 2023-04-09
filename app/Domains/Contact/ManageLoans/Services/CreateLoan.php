<?php

namespace App\Domains\Contact\ManageLoans\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Models\Loan;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CreateLoan extends BaseService implements ServiceInterface
{
    private Loan $loan;

    /** @var Collection<int,Contact> */
    private Collection $loanersCollection;

    /** @var Collection<int,Contact> */
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
            'author_must_be_vault_editor',
            'contact_must_belong_to_vault',
        ];
    }

    /**
     * Create a loan.
     */
    public function execute(array $data): Loan
    {
        $this->data = $data;
        $this->validate();
        $this->create();

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();

        return $this->loan;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->loanersCollection = collect($this->data['loaner_ids'])
            ->map(fn (string $id): Contact => $this->vault->contacts()->findOrFail($id));

        $this->loaneesCollection = collect($this->data['loanee_ids'])
            ->map(fn (string $id): Contact => $this->vault->contacts()->findOrFail($id));
    }

    private function create(): void
    {
        $this->loan = Loan::create([
            'vault_id' => $this->data['vault_id'],
            'type' => $this->data['type'],
            'name' => $this->data['name'],
            'description' => $this->valueOrNull($this->data, 'description'),
            'amount_lent' => $this->valueOrNull($this->data, 'amount_lent'),
            'currency_id' => $this->valueOrNull($this->data, 'currency_id'),
            'loaned_at' => $this->valueOrNull($this->data, 'loaned_at'),
        ]);

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
    }
}
