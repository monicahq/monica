<?php

namespace App\Settings\ManagePersonalization\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ManagePersonalization\Web\ViewHelpers\PersonalizeIndexViewHelper;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use Inertia\Inertia;

class PersonalizeController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Personalize/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => PersonalizeIndexViewHelper::data(),
        ]);
    }
}
