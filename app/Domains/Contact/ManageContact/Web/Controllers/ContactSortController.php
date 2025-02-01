<?php

namespace App\Domains\Contact\ManageContact\Web\Controllers;

use App\Domains\Contact\ManageContact\Services\UpdateContactSortOrder;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactSortController extends Controller
{
    public function update(Request $request, Vault $vault): JsonResponse
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vault->id,
            'sort_order' => $request->input('sort_order'),
        ];

        (new UpdateContactSortOrder)->execute($data);

        return response()->json([
            'data' => route('contact.index', [
                'vault' => $vault->id,
            ]),
        ], 200);
    }
}
