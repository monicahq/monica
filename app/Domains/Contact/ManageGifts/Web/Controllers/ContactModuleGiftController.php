<?php

namespace App\Domains\Contact\ManageGifts\Web\Controllers;

use App\Domains\Contact\ManageGifts\Services\CreateGift;
use App\Domains\Contact\ManageGifts\Services\DestroyGift;
use App\Domains\Contact\ManageGifts\Services\UpdateGift;
use App\Domains\Contact\ManageGifts\Web\ViewHelpers\ModuleGiftsViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactModuleGiftController extends Controller
{
    public function store(Request $request, string $vaultId, string $contactId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'description' => $request->input('description'),
            'type' => $request->input('type'),
            'name' => $request->input('name'),

        ];

        $gift = (new CreateGift())->execute($data);

        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleGiftsViewHelper::dto($contact, $gift, Auth::user()),
        ], 201);
    }

    public function update(Request $request, string $vaultId, string $contactId, int $giftId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'gift_id' => $giftId,
            'description' => $request->input('description'),
            'name' => $request->input('name'),
            'type' => $request->input('type'),
        ];

        $gift = (new UpdateGift())->execute($data);

        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleGiftsViewHelper::dto($contact, $gift, Auth::user()),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, string $contactId, int $giftId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'gift_id' => $giftId,
        ];

        (new DestroyGift())->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
