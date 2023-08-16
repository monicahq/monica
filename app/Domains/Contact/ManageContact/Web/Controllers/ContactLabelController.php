<?php

namespace App\Domains\Contact\ManageContact\Web\Controllers;

use App\Domains\Contact\ManageContact\Web\ViewHelpers\ContactIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Helpers\PaginatorHelper;
use App\Http\Controllers\Controller;
use App\Models\Label;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ContactLabelController extends Controller
{
    public function index(Request $request, string $vaultId, int $labelId)
    {
        $vault = Vault::findOrFail($vaultId);

        $contacts = Label::find($labelId)
            ->contacts()
            ->where('vault_id', $request->route()->parameter('vault'))
            ->where('listed', true)
            ->orderBy('created_at', 'asc')
            ->paginate(10);

        return Inertia::render('Vault/Contact/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => ContactIndexViewHelper::data($contacts, $vault, $labelId, Auth::user()),
            'paginator' => PaginatorHelper::getData($contacts),
        ]);
    }
}
