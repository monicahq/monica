<?php

namespace App\Domains\Settings\ManageStorage\Web\Controllers;

use App\Domains\Settings\ManageStorage\Web\ViewHelpers\StorageIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
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
