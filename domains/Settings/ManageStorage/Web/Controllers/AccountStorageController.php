<?php

namespace App\Settings\ManageStorage\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ManageStorage\Web\ViewHelpers\StorageIndexViewHelper;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AccountStorageController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Storage/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => StorageIndexViewHelper::data(Auth::user()->account),
        ]);
    }
}
