<?php

namespace App\Http\Controllers\Vault\Search;

use Inertia\Inertia;
use App\Models\Vault;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Vault\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Vault\Search\ViewHelpers\VaultSearchIndexViewHelper;

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
