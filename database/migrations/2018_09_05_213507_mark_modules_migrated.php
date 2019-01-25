<?php

use App\Models\Account\Account;
use Illuminate\Database\Migrations\Migration;

/**
 * This fixes a script that ran in 2.7.0 that duplicated all the modules for each
 * user.
 */
class MarkModulesMigrated extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Account::chunk(200, function ($accounts) {
            foreach ($accounts as $account) {
                $modules = $account->modules;
                $uniqueModules = collect([]);
                foreach ($modules as $module) {
                    $deleted = false;
                    foreach ($uniqueModules as $uniqueModule) {
                        if ($uniqueModule['translation_key'] == $module->translation_key) {
                            $module->delete();
                            $deleted = true;
                        }
                    }

                    if (! $deleted) {
                        $uniqueModules->push($module);
                    }
                }
            }
        });
    }
}
