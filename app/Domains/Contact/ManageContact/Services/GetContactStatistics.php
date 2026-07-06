<?php

namespace App\Domains\Contact\ManageContact\Services;

use App\Interfaces\ServiceInterface;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class GetContactStatistics extends BaseService implements ServiceInterface
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
        ];
    }

    /**
     * Get contact statistics for the vault.
     */
    public function execute(array $data): array
    {
        $this->data = $data;
        $this->validate();

        return $this->getStatistics();
    }

    private function validate(): void
    {
        $this->validateRules($this->data);
    }

    /**
     * Get statistics using efficient database queries.
     * Uses single query with conditional aggregation for optimal performance.
     */
    private function getStatistics(): array
    {
        $stats = DB::table('contacts')
            ->where('vault_id', $this->data['vault_id'])
            ->where('listed', true)
            ->whereNull('deleted_at')
            ->selectRaw('
                COUNT(*) as total_contacts,
                SUM(CASE WHEN is_favorite = 1 THEN 1 ELSE 0 END) as favorite_contacts,
                SUM(CASE WHEN personal_note IS NOT NULL AND personal_note != "" THEN 1 ELSE 0 END) as contacts_with_notes
            ')
            ->first();

        return [
            'total_contacts' => (int) $stats->total_contacts,
            'favorite_contacts' => (int) $stats->favorite_contacts,
            'contacts_with_notes' => (int) $stats->contacts_with_notes,
        ];
    }
}
