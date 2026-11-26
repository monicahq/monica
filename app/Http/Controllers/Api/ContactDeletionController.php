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

        $found = $this->contacts($vault, $validated['contacts']);

        $outcome = new DestroyContacts(
            user: $request->user(),
            vault: $vault,
            contacts: $found['contacts'],
        )->execute();

        return response()->json(['data' => new ContactDeletionResource([...$outcome, 'missing' => $found['missing']])]);
    }

    /**
     * @param  list<string>  $addresses
     * @return array{contacts: list<Contact>, missing: list<string>}
     */
    private function contacts(Vault $vault, array $addresses): array
    {
        $contacts = [];
        $missing = [];

        foreach ($addresses as $address) {
            $contact = Contact::query()->withTrashed()
                ->where('vault_id', $vault->id)
                ->addressedBy($address)
                ->first();

            if ($contact === null) {
                $missing[$address] = $address;

                continue;
            }

            $contacts[$contact->id] = $contact;
        }

        return ['contacts' => array_values($contacts), 'missing' => array_values($missing)];
    }
}
