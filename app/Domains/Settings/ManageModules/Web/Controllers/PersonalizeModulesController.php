<?php

namespace App\Domains\Settings\ManageModules\Web\Controllers;

use App\Domains\Settings\ManageModules\Web\ViewHelpers\PersonalizeModuleIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PersonalizeModulesController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Personalize/Modules/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => PersonalizeModuleIndexViewHelper::data(Auth::user()->account),
        ]);
    }
}
