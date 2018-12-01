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


namespace App\Http\Controllers\Settings;

use App\Models\User\Module;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ModulesController extends Controller
{
    /**
     * Get all the reminder rules.
     */
    public function index()
    {
        $modulesData = collect([]);
        $modules = auth()->user()->account->modules;

        foreach ($modules as $module) {
            $data = [
                'id' => $module->id,
                'key' => $module->key,
                'name' => trans($module->translation_key),
                'active' => $module->active,
            ];
            $modulesData->push($data);
        }

        return $modulesData;
    }

    public function toggle(Request $request, Module $module)
    {
        $module->active = ! $module->active;
        $module->save();

        return trans('settings.personalization_module_save');
    }
}
