<?php

namespace App\Domains\Settings\ManagePersonalization\Web\Controllers;

use App\Domains\Settings\ManagePersonalization\Web\ViewHelpers\PersonalizeIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
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
