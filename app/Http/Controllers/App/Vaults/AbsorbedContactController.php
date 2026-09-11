<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactMerge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Where the address of a contact that a merge absorbed lands. Route binding no longer finds a soft
 * deleted contact, so this sends the visitor to the survivor, and anything else is still not found.
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
