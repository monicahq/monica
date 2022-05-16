<?php

namespace App\Vault\Search\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Vault;
use App\Vault\Search\Web\ViewHelpers\VaultMostConsultedViewHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VaultMostConsultedContactsController extends Controller
{
    /**
     * Get the list of the most consulted contacts by the given user in the
     * current vault.
     *
     * @param  Request  $request
     * @param  int  $vaultId
     * @return void
     */
    public function index(Request $request, int $vaultId)
    {
        $vault = Vault::findOrFail($vaultId);

        return response()->json([
            'data' => VaultMostConsultedViewHelper::data($vault, Auth::user()),
        ], 200);
    }
}
