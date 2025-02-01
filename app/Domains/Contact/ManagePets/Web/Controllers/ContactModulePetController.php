<?php

namespace App\Domains\Contact\ManagePets\Web\Controllers;

use App\Domains\Contact\ManagePets\Services\CreatePet;
use App\Domains\Contact\ManagePets\Services\DestroyPet;
use App\Domains\Contact\ManagePets\Services\UpdatePet;
use App\Domains\Contact\ManagePets\Web\ViewHelpers\ModulePetsViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactModulePetController extends Controller
{
    public function store(Request $request, string $vaultId, string $contactId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'pet_category_id' => $request->input('pet_category_id'),
            'name' => $request->input('name'),
        ];

        $pet = (new CreatePet)->execute($data);

        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModulePetsViewHelper::dto($contact, $pet),
        ], 201);
    }

    public function update(Request $request, string $vaultId, string $contactId, int $petId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'pet_id' => $petId,
            'pet_category_id' => $request->input('pet_category_id'),
            'name' => $request->input('name'),
        ];

        $pet = (new UpdatePet)->execute($data);

        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModulePetsViewHelper::dto($contact, $pet),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, string $contactId, int $petId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'pet_id' => $petId,
        ];

        (new DestroyPet)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
