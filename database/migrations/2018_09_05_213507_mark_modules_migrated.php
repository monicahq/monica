<?php
/**
  *  This file is part of Monica.
  *
  *  Monica is free software: you can redistribute it and/or modify
  *  it under the terms of the GNU Affero General Public License as published by
  *  the Free Software Foundation, either version 3 of the License, or
  *  (at your option) any later version.
  *
  *  Monica is distributed in the hope that it will be useful,
  *  but WITHOUT ANY WARRANTY; without even the implied warranty of
  *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *  GNU Affero General Public License for more details.
  *
  *  You should have received a copy of the GNU Affero General Public License
  *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
  **/


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
