<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\UpdateContactNameRepresentations;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactNameRepresentation;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ContactNameController extends Controller
{
    public function update(Request $request, Vault $vault, Contact $contact): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        if ($request->missing('names') && $request->missing('names_included')) {
            $display = $contact->nameRepresentations->search(fn (ContactNameRepresentation $representation): bool => $representation->id === $contact->display_name_representation_id);

            $request->merge([
                'names' => $contact->nameRepresentations->map(fn (ContactNameRepresentation $representation): array => ['id' => $representation->uuid, ...$representation->toComparable()])->all(),
                'display' => $display === false ? null : $display,
            ]);
        }

        $request->merge(['names' => $request->input('names', [])]);

        $validated = $request->validate([
            ...UpdateContactNameRepresentations::rules('names', $contact->nameRepresentations()->pluck('uuid')->all()),
            'maiden_name' => ['nullable', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
        ], [], UpdateContactNameRepresentations::attributes('names'));

        try {
            new UpdateContactNameRepresentations(
                user: $request->user(),
                contact: $contact,
                nameRepresentations: array_values($validated['names'] ?? []),
                display: isset($validated['display']) ? (int) $validated['display'] : null,
                nickname: $validated['nickname'] ?? null,
                maidenName: $validated['maiden_name'] ?? null,
            )->execute();
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['names' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('contacts.show', ['vault' => $vault, 'contact' => $contact])
            ->with('status', __('Saved.'))
            ->with('status_description', __('They are called :name now.', ['name' => $contact->refresh()->name]));
    }
}
