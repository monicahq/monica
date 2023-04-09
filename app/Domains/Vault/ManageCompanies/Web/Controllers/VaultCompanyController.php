<?php

namespace App\Domains\Vault\ManageCompanies\Web\Controllers;

use App\Domains\Vault\ManageCompanies\Web\ViewHelpers\CompanyIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VaultCompanyController extends Controller
{
    public function index(Request $request, string $vaultId)
    {
        $vault = Vault::findOrFail($vaultId);

        return Inertia::render('Vault/Companies/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => CompanyIndexViewHelper::data($vault),
        ]);
    }
}
