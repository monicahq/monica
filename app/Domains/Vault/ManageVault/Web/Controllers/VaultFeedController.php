<?php

namespace App\Domains\Vault\ManageVault\Web\Controllers;

use App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\ModuleFeedViewHelper;
use App\Helpers\PaginatorHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VaultFeedController extends Controller
{
    public function show(Request $request, string $vaultId)
    {
        $vault = Vault::findOrFail($vaultId);

        $contactIds = Contact::where('vault_id', $vaultId)->select('id')->get()->toArray();

        $items = ContactFeedItem::whereIn('contact_id', $contactIds)
            ->with([
                'author',
                'contact' => [
                    'importantDates',
                ],
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'data' => ModuleFeedViewHelper::data($items, Auth::user(), $vault),
            'paginator' => PaginatorHelper::getData($items),
        ], 200);
    }
}
