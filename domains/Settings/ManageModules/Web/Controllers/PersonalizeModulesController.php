<?php

namespace App\Settings\ManageModules\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ManageModules\Web\ViewHelpers\PersonalizeModuleIndexViewHelper;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
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
