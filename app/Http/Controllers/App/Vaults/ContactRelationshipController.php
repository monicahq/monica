<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateRelationship;
use App\Exceptions\CannotLinkSomebodyToThemselves;
use App\Exceptions\RelationshipTypeIsNotAvailable;
use App\Exceptions\RelationshipWouldCrossVaults;
use App\Exceptions\RelationshipWouldLoopParentage;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\RelationshipType;
use App\Models\Vault;
use App\ViewModels\Vaults\RelationshipFormViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactRelationshipController extends Controller
{
    public function new(Request $request, Vault $vault, Contact $contact): View
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        return view('app.vaults.contacts.relationships.form', [
            'viewModel' => new RelationshipFormViewModel(
                user: $request->user(),
                vault: $vault,
                contact: $contact,
                query: (string) $request->string('query'),
            ),
        ]);
    }

    public function create(Request $request, Vault $vault, Contact $contact): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $validated = $request->validate($this->rules($request));

        $relationshipType = RelationshipType::query()
            ->where('account_id', $request->user()->account_id)
            ->findOrFail($validated['relationship_type_id']);

        $otherContact = $validated['mode'] === 'existing'
            ? Contact::query()->where('vault_id', $vault->id)->findOrFail($validated['other_contact_id'])
            : null;

        try {
            new CreateRelationship(
                user: $request->user(),
                vault: $vault,
                contact: $contact,
                relationshipType: $relationshipType,
                otherContact: $otherContact,
                firstName: $validated['mode'] === 'known' ? ($validated['first_name'] ?? null) : null,
                lastName: $validated['mode'] === 'known' ? ($validated['last_name'] ?? null) : null,
                createsRecord: $request->boolean('creates_record'),
                swapped: $request->boolean('swapped'),
            )->execute();
        } catch (CannotLinkSomebodyToThemselves|RelationshipWouldCrossVaults $e) {
            return back()->withInput()->withErrors(['other_contact_id' => $e->getMessage()]);
        } catch (RelationshipTypeIsNotAvailable $e) {
            return back()->withInput()->withErrors(['relationship_type_id' => $e->getMessage()]);
        } catch (RelationshipWouldLoopParentage $e) {
            return back()->withInput()->withErrors(['mode' => $e->getMessage()]);
        }

        return redirect()->route('contacts.show', ['vault' => $vault, 'contact' => $contact])
            ->with('status', __('The relationship is recorded.'))
            ->with('status_description', __('It reads from both records at once, so there is no reverse to add.'));
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(Request $request): array
    {
        return [
            'relationship_type_id' => [
                'required',
                'integer',
                Rule::exists('relationship_types', 'id')->where('account_id', $request->user()->account_id),
            ],
            'mode' => ['required', 'string', Rule::in(['unknown', 'known', 'existing'])],
            'other_contact_id' => ['required_if:mode,existing', 'nullable', 'integer'],
            'first_name' => ['required_if:mode,known', 'nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'creates_record' => ['nullable', 'boolean'],
            'swapped' => ['nullable', 'boolean'],
        ];
    }
}
