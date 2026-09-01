<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\FavouriteContact;
use App\Actions\UnfavouriteContact;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Whether a contact sits at the top of the list.
 *
 * The request carries the state it wants rather than asking for a toggle. A page
 * left open while the mark changed elsewhere would otherwise invert whatever it
 * found, which is the opposite of what its own star was showing. Sending the
 * state makes the request idempotent, so the worst a stale page can do is
 * confirm what is already stored.
 *
 * Nothing is flashed. The star is the answer, and a message about a mark the
 * user just watched change under their finger says nothing they did not see.
 */
class ContactFavouriteController extends Controller
{
    public function update(Request $request, Vault $vault, Contact $contact): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $request->validate(['favourite' => ['required', 'boolean']]);

        if ($request->boolean('favourite')) {
            new FavouriteContact(user: $request->user(), contact: $contact)->execute();

            return redirect()->route('contacts.show', ['vault' => $vault, 'contact' => $contact]);
        }

        new UnfavouriteContact(user: $request->user(), contact: $contact)->execute();

        return redirect()->route('contacts.show', ['vault' => $vault, 'contact' => $contact]);
    }
}
