<?php

namespace App\Domains\BlackBookd\Web\Controllers;

use App\Domains\BlackBookd\Web\ViewHelpers\VaultBlackBookdIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VaultBlackBookdController extends Controller
{
    public function index(Vault $vault)
    {
        abort_unless(Auth::user()->account->blackbookd_enabled, 403);

        return Inertia::render('Vault/BlackBookd/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => VaultBlackBookdIndexViewHelper::data($vault, Auth::user()),
        ]);
    }

    public function pipeline(Vault $vault)
    {
        abort_unless(Auth::user()->account->blackbookd_enabled, 403);

        return Inertia::render('Vault/BlackBookd/PipelineBoard', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => VaultBlackBookdIndexViewHelper::pipelineBoard($vault),
        ]);
    }
}