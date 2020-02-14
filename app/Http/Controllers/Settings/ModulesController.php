<?php

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

        return response()->json([
            'data' => [
                'id' => $module->id,
                'key' => $module->key,
                'name' => trans($module->translation_key),
                'active' => $module->active,
            ],
        ], 200);
    }
}
