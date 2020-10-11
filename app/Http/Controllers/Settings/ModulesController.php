<?php

namespace App\Http\Controllers\Settings;

use App\Models\User\Module;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\JsonRespondController;

class ModulesController extends Controller
{
    use JsonRespondController;

    /**
     * Get all the reminder rules.
     */
    public function index()
    {
        $modules = auth()->user()->account->modules;

        return $modules->map(function ($module) {
            return $this->format($module);
        });
    }

    public function toggle(Request $request, Module $module)
    {
        $module->active = ! $module->active;
        $module->save();

        return $this->respond([
            'data' => $this->format($module),
        ]);
    }

    private function format(Module $module)
    {
        return [
            'id' => $module->id,
            'key' => $module->key,
            'name' => trans($module->translation_key),
            'active' => $module->active,
        ];
    }
}
