<?php

namespace App\Domains\Vault\Search\Web\Controllers;

use App\Domains\Vault\Search\Web\ViewHelpers\VaultContactSearchViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\Request;

/**
 * This method is used to search contacts in the modules like "Activity", or
 * "loans".
 */
class VaultContactSearchController extends Controller
{
    public function index(Request $request, string $vaultId)
    {
        $vault = Vault::findOrFail($vaultId);

        return response()->json([
            'data' => VaultContactSearchViewHelper::data($vault, $request->input('searchTerm')),
        ], 200);
    }
}
