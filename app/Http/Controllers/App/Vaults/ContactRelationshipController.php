<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateRelationship;
use App\Actions\DestroyRelationship;
use App\Actions\UpdateRelationship;
use App\Enums\RelationshipStatusEnum;
use App\Exceptions\CannotLinkSomebodyToThemselves;
use App\Exceptions\RelationshipDatesAreOutOfOrder;
use App\Exceptions\RelationshipTypeIsNotAvailable;
use App\Exceptions\RelationshipWouldCrossVaults;
use App\Exceptions\RelationshipWouldLoopParentage;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Relationship;
use App\Models\RelationshipType;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactViewModel;
use App\ViewModels\Vaults\RelationshipEditViewModel;
use App\ViewModels\Vaults\RelationshipFormViewModel;
use Carbon\CarbonImmutable;
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
                preselectedTypeId: (string) $request->string('type'),
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

        return redirect()->route('contacts.show', [
            'vault' => $vault,
            'contact' => $contact,
            'page' => ContactViewModel::PAGE_RELATIONSHIPS,
        ])
            ->with('status', __('The relationship is recorded.'))
            ->with('status_description', __('It reads from both records at once, so there is no reverse to add.'));
    }

    public function edit(Request $request, Vault $vault, Contact $contact, Relationship $relationship): View
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);
        abort_unless($relationship->vault_id === $vault->id, 404);

        return view('app.vaults.contacts.relationships.edit', [
            'viewModel' => new RelationshipEditViewModel(
                user: $request->user(),
                vault: $vault,
                contact: $contact,
                relationship: $relationship,
            ),
        ]);
    }

    public function update(Request $request, Vault $vault, Contact $contact, Relationship $relationship): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);
        abort_unless($relationship->vault_id === $vault->id, 404);

        $validated = $request->validate([
            'relationship_type_id' => [
                'required',
                'integer',
                Rule::exists('relationship_types', 'id')->where('account_id', $request->user()->account_id),
            ],
            'status' => ['required', 'string', Rule::in(RelationshipStatusEnum::values())],
            'started_on' => ['nullable', 'date'],
            'ended_on' => ['nullable', 'date'],
            'swapped' => ['nullable', 'boolean'],
        ]);

        $relationshipType = RelationshipType::query()
            ->where('account_id', $request->user()->account_id)
            ->findOrFail($validated['relationship_type_id']);

        try {
            new UpdateRelationship(
                user: $request->user(),
                vault: $vault,
                relationship: $relationship,
                relationshipType: $relationshipType,
                status: RelationshipStatusEnum::from($validated['status']),
                startedOn: $validated['started_on'] === null ? null : CarbonImmutable::parse($validated['started_on']),
                endedOn: $validated['ended_on'] === null ? null : CarbonImmutable::parse($validated['ended_on']),
                swapped: $request->boolean('swapped'),
            )->execute();
        } catch (RelationshipTypeIsNotAvailable $e) {
            return back()->withInput()->withErrors(['relationship_type_id' => $e->getMessage()]);
        } catch (RelationshipDatesAreOutOfOrder $e) {
            return back()->withInput()->withErrors(['ended_on' => $e->getMessage()]);
        } catch (RelationshipWouldLoopParentage $e) {
            return back()->withInput()->withErrors(['relationship_type_id' => $e->getMessage()]);
        }

        return $this->backToTheTab($vault, $contact, __('The relationship is changed.'));
    }

    public function destroy(Request $request, Vault $vault, Contact $contact, Relationship $relationship): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);
        abort_unless($relationship->vault_id === $vault->id, 404);

        new DestroyRelationship(
            user: $request->user(),
            vault: $vault,
            relationship: $relationship,
        )->execute();

        return $this->backToTheTab($vault, $contact, __('The relationship is removed.'));
    }

    private function backToTheTab(Vault $vault, Contact $contact, string $status): RedirectResponse
    {
        return redirect()->route('contacts.show', [
            'vault' => $vault,
            'contact' => $contact,
            'page' => ContactViewModel::PAGE_RELATIONSHIPS,
        ])->with('status', $status);
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
