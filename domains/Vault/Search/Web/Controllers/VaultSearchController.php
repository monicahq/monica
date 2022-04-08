<?php

namespace App\Vault\Search\Web\Controllers;

use Inertia\Inertia;
use App\Models\Vault;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Vault\Search\Web\ViewHelpers\VaultSearchIndexViewHelper;

class VaultSearchController extends Controller
{
    public function index(Request $request, int $vaultId)
    {
        $vault = Vault::findOrFail($vaultId);

        return Inertia::render('Vault/Search/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => VaultSearchIndexViewHelper::data($vault),
        ]);
    }

    public function show(Request $request, int $vaultId)
    {
        $vault = Vault::findOrFail($vaultId);

        return response()->json([
            'data' => VaultSearchIndexViewHelper::data($vault, $request->input('searchTerm')),
        ], 200);
    }
}
