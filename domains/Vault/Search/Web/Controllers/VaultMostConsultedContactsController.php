<?php

namespace App\Vault\Search\Web\Controllers;

use App\Models\Vault;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Vault\Search\Web\ViewHelpers\VaultMostConsultedViewHelper;

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
