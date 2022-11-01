<?php

namespace App\Domains\Contact\ManageContact\Web\Controllers;

use App\Domains\Contact\ManageContact\Web\ViewHelpers\ContactIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Helpers\PaginatorHelper;
use App\Http\Controllers\Controller;
use App\Models\Label;
use App\Models\Vault;
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
            'paginator' => PaginatorHelper::getData($contacts),
        ]);
    }
}
