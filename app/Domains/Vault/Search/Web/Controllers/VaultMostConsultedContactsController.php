<?php

namespace App\Domains\Vault\Search\Web\Controllers;

use App\Domains\Vault\Search\Web\ViewHelpers\VaultMostConsultedViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VaultMostConsultedContactsController extends Controller
{
    /**
     * Get the list of the most consulted contacts by the given user in the
     * current vault.
     */
    public function index(Request $request, string $vaultId): JsonResponse
    {
        $vault = Vault::findOrFail($vaultId);

        return response()->json([
            'data' => VaultMostConsultedViewHelper::data($vault, Auth::user()),
        ], 200);
    }
}
