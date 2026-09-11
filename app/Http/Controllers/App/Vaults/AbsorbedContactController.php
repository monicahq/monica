<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactMerge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Where the address of a contact that was absorbed by a merge lands.
 *
 * A merged contact is soft deleted, so route binding no longer finds it and the
 * page would be a 404. This runs instead: if the uuid belongs to a contact that
 * a merge absorbed and nobody has reversed, the visitor goes to the survivor.
 * Anything else is still not found, so nothing here says whether a uuid ever
 * existed in an account the visitor cannot reach.
 */
class AbsorbedContactController extends Controller
{
    public function show(Request $request): RedirectResponse
    {
        $vault = $request->route('vault');
        $uuid = $request->route('contact');

        abort_unless(is_string($uuid), 404);

        $absorbed = Contact::withTrashed()
            ->where('uuid', $uuid)
            ->where('account_id', $request->user()->account_id)
            ->first();

        abort_if($absorbed === null, 404);

        $merge = ContactMerge::query()
            ->where('merged_contact_id', $absorbed->id)
            ->whereNull('undone_at')
            ->latest('id')
            ->first();

        abort_if($merge === null, 404);

        return redirect()->route('contacts.show', [
            'vault' => $vault ?? $merge->vault_id,
            'contact' => $merge->keptContact,
        ]);
    }
}
