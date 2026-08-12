<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\DestroyContactAvatar;
use App\Actions\UpdateContactAvatar;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The picture of a contact, which is not a field either.
 *
 * It is written from the same screen as the name, drawn by
 * {@see ContactController::edit()}, because it is the same kind of thing: what
 * the product asks about somebody whatever template they are on. This is only
 * the write, and it is its own controller because the picture is its own thing
 * being written.
 *
 * The upload becomes a file of the vault, and taking it off lets that file go
 * rather than deleting it.
 */
class ContactAvatarController extends Controller
{
    public function update(Request $request, Vault $vault, Contact $contact): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $validated = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        new UpdateContactAvatar(
            user: $request->user(),
            vault: $vault,
            contact: $contact,
            upload: $validated['avatar'],
        )->execute();

        return redirect()
            ->route('contacts.edit', ['vault' => $vault, 'contact' => $contact])
            ->with('status', __('The picture is saved.'))
            ->with('status_description', __('It is what :name is drawn as from now on.', ['name' => $contact->name]));
    }

    public function destroy(Request $request, Vault $vault, Contact $contact): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        new DestroyContactAvatar(
            user: $request->user(),
            vault: $vault,
            contact: $contact,
        )->execute();

        return redirect()
            ->route('contacts.edit', ['vault' => $vault, 'contact' => $contact])
            ->with('status', __('The picture is removed.'))
            ->with('status_description', __('We draw the initials of :name again.', ['name' => $contact->name]));
    }
}
