<?php

namespace App\Services\Auth\Population;

use App\Models\User\Module;
use App\Services\BaseService;
use App\Models\Account\Account;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use App\Exceptions\MissingParameterException;

/**
 * Populate the modules table for a given account.
 */
class PopulateModulesTable extends BaseService
{
    /**
     * The structure that the method expects to receive as parameter.
     *
     * @var array
     */
    private $structure = [
        'account_id',
        'migrate_existing_data',
    ];

    /**
     * The data needed for the query to be executed.
     *
     * @var array
     */
    private $data;

    /**
     * Execute the service.
     *
     * @param array $data
     * @return void
     */
    public function execute(array $givenData)
    {
        $this->data = $givenData;

        if (! $this->validateDataStructure($this->data, $this->structure)) {
            throw new MissingParameterException('Missing parameters');
        }

        $this->createEntries();

        $this->markTableAsMigrated();
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

    /**
     * Mark the table as migrated.
     *
     * @return void
     */
    private function markTableAsMigrated()
    {
        DB::table('default_contact_modules')
            ->update(['migrated' => 1]);
    }
}
