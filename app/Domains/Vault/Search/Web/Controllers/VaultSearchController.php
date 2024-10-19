<?php

namespace App\Domains\Vault\Search\Web\Controllers;

use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Domains\Vault\Search\Web\ViewHelpers\VaultSearchIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VaultSearchController extends Controller
{
    public function index(Request $request, string $vaultId)
    {
        $vault = Vault::findOrFail($vaultId);

        return Inertia::render('Vault/Search/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => VaultSearchIndexViewHelper::data($vault, $request->input('searchTerm')),
        ]);
    }

    public function show(Request $request, string $vaultId)
    {
        $vault = Vault::findOrFail($vaultId);

        return response()->json([
            'data' => VaultSearchIndexViewHelper::data($vault, $request->input('searchTerm')),
        ], 200);
    }
}
