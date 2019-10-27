<?php

namespace App\Services\Auth\Population;

use App\Models\User\Module;
use App\Services\BaseService;
use App\Models\Account\Account;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

/**
 * Populate the modules table for a given account.
 */
class PopulateModulesTable extends BaseService
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
     * The data needed for the query to be executed.
     *
     * @var array
     */
    private $data;

    /**
     * Execute the service.
     *
     * @param array $givenData
     * @return bool
     */
    public function execute(array $givenData) : bool
    {
        $this->data = $givenData;

        if (! $this->validate($this->data)) {
            return false;
        }

        $this->createEntries();

        return true;
    }

    /**
     * Create modules entries.
     *
     * @return void
     */
    private function createEntries()
    {
        $defaultModules = $this->getDefaultModules();

        foreach ($defaultModules as $defaultModule) {
            $this->feedModule($defaultModule);
        }
    }

    /**
     * Get the default modules.
     *
     * @throws QueryException if the query does not run for some reasons.
     * @return Collection
     */
    private function getDefaultModules()
    {
        if ($this->data['migrate_existing_data'] == 1) {
            $defaultModules = DB::table('default_contact_modules')
                ->get();
        } else {
            $defaultModules = DB::table('default_contact_modules')
                ->where('migrated', 0)
                ->get();
        }

        return $defaultModules;
    }

    /**
     * Create an entry in the module table.
     *
     * @param object $defaultModule
     * @return void
     */
    private function feedModule($defaultModule)
    {
        Module::create([
            'account_id' => $this->data['account_id'],
            'key' => $defaultModule->key,
            'translation_key' => $defaultModule->translation_key,
            'delible' => $defaultModule->delible,
            'active' => $defaultModule->active,
        ]);
    }
}
