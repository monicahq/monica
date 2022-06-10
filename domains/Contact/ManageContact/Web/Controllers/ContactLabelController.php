<?php

namespace App\Contact\ManageContact\Web\Controllers;

use App\Contact\ManageContact\Web\ViewHelpers\ContactIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Label;
use App\Models\Vault;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactLabelController extends Controller
{
    public function index(Request $request, int $vaultId, int $labelId)
    {
        $vault = Vault::findOrFail($vaultId);

        $contacts = Label::where('id', $labelId)->first()
            ->contacts()
            ->where('vault_id', $request->route()->parameter('vault'))
            ->where('listed', true)
            ->orderBy('created_at', 'asc')
            ->paginate(10);

        return Inertia::render('Vault/Contact/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => ContactIndexViewHelper::data($contacts, $vault, $labelId),
        ]);
    }
}
