<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\MergeContacts;
use App\Actions\UndoContactMerge;
use App\Exceptions\CannotAbsorbTheMeContact;
use App\Exceptions\CannotMergeAContactWithItself;
use App\Exceptions\ContactChangedSinceThePreview;
use App\Exceptions\ContactMergeCannotBeUndone;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactMerge;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactMergeViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactMergeController extends Controller
{
    public function new(Request $request, Vault $vault, Contact $contact): View
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        return view('app.vaults.contacts.merge.new', [
            'viewModel' => new ContactMergeViewModel(
                user: $request->user(),
                vault: $vault,
                contact: $contact,
            ),
        ]);
    }

    public function show(Request $request, Vault $vault, Contact $contact, Contact $other): View
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);
        abort_unless($other->isReachableThrough($request->user(), $vault), 404);

        return view('app.vaults.contacts.merge.show', [
            'viewModel' => new ContactMergeViewModel(
                user: $request->user(),
                vault: $vault,
                contact: $contact,
                other: $other,
            ),
        ]);
    }

    public function create(Request $request, Vault $vault, Contact $contact, Contact $other): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);
        abort_unless($other->isReachableThrough($request->user(), $vault), 404);

        $validated = $request->validate([
            'fingerprint' => ['required', 'string'],
            'resolutions' => ['array'],
            'resolutions.*.field_key' => ['required', 'string'],
            'resolutions.*.row' => ['nullable', 'integer'],
            'resolutions.*.keep' => ['required', 'string', 'in:kept,other,both'],
        ]);

        $absorbedName = $other->name;

        try {
            new MergeContacts(
                user: $request->user(),
                kept: $contact,
                absorbed: $other,
                resolutions: array_values($validated['resolutions'] ?? []),
                fingerprint: $validated['fingerprint'],
            )->execute();
        } catch (ContactChangedSinceThePreview $e) {
            return redirect()
                ->route('contacts.merge.show', ['vault' => $vault, 'contact' => $contact, 'other' => $other])
                ->withErrors(['fingerprint' => $e->getMessage()]);
        } catch (CannotAbsorbTheMeContact|CannotMergeAContactWithItself $e) {
            return redirect()
                ->route('contacts.merge.new', ['vault' => $vault, 'contact' => $contact])
                ->withErrors(['other' => $e->getMessage()]);
        }

        return redirect()
            ->route('contacts.show', ['vault' => $vault, 'contact' => $contact])
            ->with('status', __(':name is one contact now.', ['name' => $contact->name]))
            ->with('status_description', __('Everything :name held came over, and nothing was thrown away.', ['name' => $absorbedName]));
    }

    public function destroy(Request $request, Vault $vault, ContactMerge $contactMerge): RedirectResponse
    {
        abort_unless($contactMerge->vault_id === $vault->id, 404);

        $survivor = $contactMerge->keptContact;

        try {
            new UndoContactMerge(user: $request->user(), merge: $contactMerge)->execute();
        } catch (ContactMergeCannotBeUndone $e) {
            return redirect()
                ->route('contacts.show', ['vault' => $vault, 'contact' => $survivor])
                ->withErrors(['merge' => $e->getMessage()]);
        }

        return redirect()
            ->route('contacts.show', ['vault' => $vault, 'contact' => $survivor])
            ->with('status', __('The merge is undone.'))
            ->with('status_description', __('Both people are back, each holding what they held before.'));
    }
}
