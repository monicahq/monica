<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\DestroyContactAvatar;
use App\Actions\UpdateContactAvatar;
use App\Exceptions\NotEnoughStorageLeft;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactAvatarController extends Controller
{
    public function update(Request $request, Vault $vault, Contact $contact): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $validated = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        try {
            new UpdateContactAvatar(
                user: $request->user(),
                vault: $vault,
                contact: $contact,
                upload: $validated['avatar'],
            )->execute();
        } catch (NotEnoughStorageLeft $e) {
            return back()->withErrors(['avatar' => $e->getMessage()]);
        }

        return redirect()
            ->route('contacts.edit', ['vault' => $vault, 'contact' => $contact])
            ->with('status', __('The photo is saved.'))
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
            ->with('status', __('The photo is removed.'))
            ->with('status_description', __('We draw the initials of :name again.', ['name' => $contact->name]));
    }
}
