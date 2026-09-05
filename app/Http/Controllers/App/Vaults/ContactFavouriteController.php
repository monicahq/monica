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
