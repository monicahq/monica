<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\DestroyContacts;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContactDeletionResource;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ContactDeletionController extends Controller
{
    public function destroy(Request $request, string $vault): JsonResponse
    {
        $vault = $request->user()->account->vaults()
            ->addressedBy($vault)
            ->firstOrFail();

        $validated = $request->validate([
            'contacts' => ['required', 'array', 'min:1', 'max:'.DestroyContacts::LIMIT],
            'contacts.*' => ['required', 'string', 'max:255'],
        ]);

        $outcome = new DestroyContacts(
            user: $request->user(),
            vault: $vault,
            contacts: $this->contacts($vault, $validated['contacts']),
        )->execute();

        return response()->json(['data' => new ContactDeletionResource($outcome)]);
    }

    /**
     * @param  list<string>  $addresses
     * @return list<Contact>
     */
    private function contacts(Vault $vault, array $addresses): array
    {
        $contacts = [];

        foreach ($addresses as $index => $address) {
            $contact = Contact::query()->withTrashed()
                ->where('vault_id', $vault->id)
                ->addressedBy($address)
                ->first();

            if ($contact === null) {
                throw ValidationException::withMessages([
                    "contacts.{$index}" => __('That contact does not exist.'),
                ]);
            }

            $contacts[$contact->id] = $contact;
        }

        return array_values($contacts);
    }
}
