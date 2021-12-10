<?php

namespace App\Http\Controllers\Vault;

use Inertia\Inertia;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\Vault\ManageVault\CreateVault;

class VaultController extends Controller
{
    /**
     * Show all the vaults of the user.
     *
     * @return Response
     */
    public function index()
    {
        return Inertia::render('Vault/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => VaultIndexViewHelper::data(Auth::user()->account),
        ]);
    }

    /**
     * Display the create vault page.
     *
     * @return Response
     */
    public function new()
    {
        return Inertia::render('Vault/Create', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => VaultCreateViewHelper::data(),
        ]);
    }

    /**
     * Store the vault.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'type' => Vault::TYPE_PERSONAL,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
        ];

        (new CreateVault)->execute($data);

        return response()->json([
            'data' => route('vault.index'),
        ], 201);
    }

    /**
     * Display the vault.
     *
     * @param  Request  $request
     * @param  int  $vaultId
     * @return Response
     */
    public function show(Request $request, int $vaultId)
    {
        $vault = Vault::find($vaultId);

        return Inertia::render('Vault/Show', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => VaultCreateViewHelper::data(),
        ]);
    }
}
