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
 * Both branches flash. The star is small, it sits in the corner of an avatar and
 * it changes without moving, so on its own it is easy to miss; every other save
 * in the product answers in the same corner, and the one that stayed silent read
 * as the one that had not landed. The wording names the state the contact ends
 * in rather than the act, which is what keeps it true when a stale page confirms
 * a mark that was already stored.
 */
class ContactFavouriteController extends Controller
{
    public function update(Request $request, Vault $vault, Contact $contact): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $request->validate(['favourite' => ['required', 'boolean']]);

        if ($request->boolean('favourite')) {
            new FavouriteContact(user: $request->user(), contact: $contact)->execute();

            return redirect()->route('contacts.show', ['vault' => $vault, 'contact' => $contact])
                ->with('status', __('Marked as a favourite.'))
                ->with('status_description', __('They sit at the top of the list.'));
        }

        new UnfavouriteContact(user: $request->user(), contact: $contact)->execute();

        return redirect()->route('contacts.show', ['vault' => $vault, 'contact' => $contact])
            ->with('status', __('No longer a favourite.'))
            ->with('status_description', __('They go back in with everybody else.'));
    }
}
