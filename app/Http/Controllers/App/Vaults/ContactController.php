<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateContact;
use App\Actions\DestroyContact;
use App\Actions\UpdateContactListView;
use App\Enums\ContactGroupingEnum;
use App\Enums\ContactSortEnum;
use App\Enums\NameOrderEnum;
use App\Enums\SortDirectionEnum;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\StructureVersion;
use App\Models\Vault;
use App\Rules\PublishedContactStructureVersion;
use App\ViewModels\Vaults\ContactEditViewModel;
use App\ViewModels\Vaults\ContactFormViewModel;
use App\ViewModels\Vaults\ContactsViewModel;
use App\ViewModels\Vaults\ContactViewModel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(Request $request, Vault $vault, ?string $filter = null): View|RedirectResponse
    {
        $user = $request->user();
        $grouping = ContactGroupingEnum::fromValueOrEveryone($filter ?? $user->contact_list_grouping);
        $sort = ContactSortEnum::fromValueOrName($request->string('sort')->toString() ?: $user->contact_list_sort);
        $direction = SortDirectionEnum::fromValueOrAscending($request->string('direction')->toString() ?: $user->contact_list_direction);

        if ($filter === null) {
            return redirect()->route('contacts.index', [
                'vault' => $vault,
                'filter' => $grouping->value,
                'sort' => $sort->value,
                'direction' => $direction->value,
            ]);
        }

        if (! $request->prefetch()) {
            new UpdateContactListView(
                user: $user,
                grouping: $grouping,
                sort: $sort,
                direction: $direction,
            )->execute();
        }

        return view('app.vaults.contacts.index', [
            'viewModel' => new ContactsViewModel(
                user: $user,
                vault: $vault,
                query: (string) $request->string('query'),
                grouping: $grouping,
                sort: $sort,
                direction: $direction,
            ),
        ]);
    }

    public function show(Request $request, Vault $vault, Contact $contact, ?string $page = null): View
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        return view('app.vaults.contacts.show', [
            'viewModel' => new ContactViewModel(
                user: $request->user(),
                vault: $vault,
                contact: $contact,
                selectedPageKey: $page,
                timelinePage: $request->integer('timelinePage', 1),
                lifeEventJustAddedId: $request->session()->get('lifeEventJustAdded'),
                lifeEventEditedId: $request->session()->get('lifeEventEdited'),
                deletedLifeEventUuid: $request->session()->get('lifeEventDeleted'),
                deletedLifeEventLine: $request->session()->get('lifeEventDeletedLine'),
            ),
        ]);
    }

    public function edit(Request $request, Vault $vault, Contact $contact): View
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        return view('app.vaults.contacts.edit', [
            'viewModel' => new ContactEditViewModel(
                user: $request->user(),
                vault: $vault,
                contact: $contact,
            ),
        ]);
    }

    public function destroy(Request $request, Vault $vault, Contact $contact): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $name = $contact->name;

        try {
            new DestroyContact(user: $request->user(), contact: $contact)->execute();
        } catch (AuthorizationException $e) {
            return redirect()
                ->route('contacts.edit', ['vault' => $vault, 'contact' => $contact])
                ->withErrors(['contact' => $e->getMessage()]);
        }

        return redirect()
            ->route('contacts.index', $vault)
            ->with('status', __(':name is deleted.', ['name' => $name]))
            ->with('status_description', __('Nothing they took part in went with them.'));
    }

    public function new(Request $request, Vault $vault): View
    {
        return view('app.vaults.contacts.form', [
            'viewModel' => new ContactFormViewModel(
                user: $request->user(),
                vault: $vault,
            ),
        ]);
    }

    public function create(Request $request, Vault $vault): RedirectResponse
    {
        $validated = $request->validate($this->rules($request));
        $nameOrder = $validated['name_order'] ?? null;

        try {
            $contact = new CreateContact(
                user: $request->user(),
                vault: $vault,
                structureVersion: $this->versionAskedFor($request),
                prefix: $validated['prefix'] ?? null,
                firstName: $validated['first_name'],
                middleName: $validated['middle_name'] ?? null,
                lastName: $validated['last_name'] ?? null,
                suffix: $validated['suffix'] ?? null,
                maidenName: $validated['maiden_name'] ?? null,
                nickname: $validated['nickname'] ?? null,
                nameOrder: $nameOrder === null ? null : NameOrderEnum::from($nameOrder),
                isPartial: $request->boolean('is_partial'),
                isFavourite: $request->boolean('is_favourite'),
            )->execute();
        } catch (AuthorizationException $e) {
            return back()->withErrors(['first_name' => $e->getMessage()])->withInput();
        }

        $destination = $request->boolean('keep_going')
            ? redirect()->route('contacts.new', $vault)
            : redirect()->route('contacts.show', ['vault' => $vault, 'contact' => $contact]);

        return $destination
            ->with('status', __(':name is written down.', ['name' => $contact->name]))
            ->with('status_description', $contact->is_partial
                ? __('A partial contact stays off the list of contacts until they are more than a first name.')
                : __('The rest of the page is yours to fill in whenever you know it.'));
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(Request $request): array
    {
        return [
            'prefix' => ['nullable', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'maiden_name' => ['nullable', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'name_order' => ['nullable', Rule::enum(NameOrderEnum::class)],
            'is_partial' => ['nullable', 'boolean'],
            'is_favourite' => ['nullable', 'boolean'],
            'keep_going' => ['nullable', 'boolean'],
            'structure_version_id' => [
                'nullable',
                'integer',
                new PublishedContactStructureVersion($request->user()->account_id),
            ],
        ];
    }

    private function versionAskedFor(Request $request): ?StructureVersion
    {
        if (! $request->filled('structure_version_id')) {
            return null;
        }

        return StructureVersion::query()->find($request->integer('structure_version_id'));
    }
}
