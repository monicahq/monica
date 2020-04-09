<?php

namespace App\Services\Auth\Population;

use App\Services\BaseService;
use App\Models\Account\Account;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use App\Models\Contact\ContactFieldType;

/**
 * Populate the contact field types table for a given account.
 * This is typically done when a new account is created.
 */
class PopulateContactFieldTypesTable extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'migrate_existing_data' => 'required|boolean',
        ];
    }

    /**
     * Execute the service.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data): bool
    {
        $this->createEntries($data);

        return true;
    }

    /**
     * Create contact field type entries.
     *
     * @param array $data
     * @return void
     */
    private function createEntries($data)
    {
        $defaultContactFieldTypes = $this->getDefaultContactFieldTypes($data);

        foreach ($defaultContactFieldTypes as $defaultContactFieldType) {
            $this->createEntry($defaultContactFieldType, $data);
        }
    }

    /**
     * Get the default contact field types.
     *
     * @param array $data
     * @throws QueryException if the query does not run for some reasons.
     * @return Collection
     */
    private function getDefaultContactFieldTypes($data)
    {
        if ($data['migrate_existing_data'] == 1) {
            $defaultContactFieldTypes = DB::table('default_contact_field_types')
                ->get();
        } else {
            $defaultContactFieldTypes = DB::table('default_contact_field_types')
                ->where('migrated', 0)
                ->get();
        }

        return $defaultContactFieldTypes;
    }

    /**
     * Create an entry in the life event category table.
     *
     * @param object $defaultContactFieldType
     * @param array $data
     * @return void
     */
    private function createEntry($defaultContactFieldType, $data)
    {
        ContactFieldType::create([
            'account_id' => $data['account_id'],
            'name' => $defaultContactFieldType->name,
            'fontawesome_icon' => (is_null($defaultContactFieldType->fontawesome_icon) ? null : $defaultContactFieldType->fontawesome_icon),
            'protocol' => (is_null($defaultContactFieldType->protocol) ? null : $defaultContactFieldType->protocol),
            'delible' => $defaultContactFieldType->delible,
            'type' => (is_null($defaultContactFieldType->type) ? null : $defaultContactFieldType->type),
        ]);
    }
}
