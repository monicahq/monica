<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateContact;
use App\Actions\DestroyContact;
use App\Actions\UpdateContactListView;
use App\Actions\UpdateContactNameRepresentations;
use App\Enums\ContactGroupingEnum;
use App\Enums\ContactSortEnum;
use App\Enums\SortDirectionEnum;
use App\Enums\ValueListEnum;
use App\Exceptions\VaultHasNoContactDefault;
use App\Helpers\AlpineAjaxTargets;
use App\Helpers\PageSize;
use App\Http\Controllers\Controller;
use App\Jobs\RecordContactVisit;
use App\Models\Contact;
use App\Models\StructureVersion;
use App\Models\ValueList;
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
use InvalidArgumentException;

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
                'categories' => $request->input('categories'),
            ]);
        }

        $validated = $request->validate([
            'categories' => ['array'],
            'categories.*' => ['integer', Rule::exists('value_list_items', 'id')
                ->where('account_id', $user->account_id)
                ->where('value_list_id', ValueList::query()
                    ->where('account_id', $user->account_id)
                    ->where('key', ValueListEnum::ContactCategories->value)
                    ->value('id'))],
        ]);

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
                size: PageSize::from($request, ContactsViewModel::PER_PAGE),
                categoryIds: array_map(intval(...), $validated['categories'] ?? []),
            ),
        ]);
    }

    public function show(Request $request, Vault $vault, Contact $contact, ?string $page = null): View
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        RecordContactVisit::dispatch(contact: $contact)->onQueue('low');

        $viewModel = new ContactViewModel(
            user: $request->user(),
            vault: $vault,
            contact: $contact,
            selectedPageKey: $page,
            timelinePage: $request->integer('timelinePage', 1),
            lifeEventJustAddedId: $request->session()->get('lifeEventJustAdded'),
            lifeEventEditedId: $request->session()->get('lifeEventEdited'),
            deletedLifeEventUuid: $request->session()->get('lifeEventDeleted'),
            deletedLifeEventLine: $request->session()->get('lifeEventDeletedLine'),
        );

        $targets = AlpineAjaxTargets::from($request);

        return view($viewModel->drawsTargetsAlone($targets) ? 'app.vaults.contacts.panels' : 'app.vaults.contacts.show', [
            'viewModel' => $viewModel,
            'targets' => $targets,
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
                askedTemplate: $request->filled('template') ? (string) $request->string('template') : null,
                askedVersion: $request->filled('version') ? $request->integer('version') : null,
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
        $validated = $request->validate($this->rules($request), [], UpdateContactNameRepresentations::attributes('names'));

        try {
            $contact = new CreateContact(
                user: $request->user(),
                vault: $vault,
                structureVersion: $this->versionAskedFor($request),
                maidenName: $validated['maiden_name'] ?? null,
                nickname: $validated['nickname'] ?? null,
                isPartial: $request->boolean('is_partial'),
                isFavourite: $request->boolean('is_favourite'),
                nameRepresentations: array_values($validated['names']),
                display: (int) $validated['display'],
            )->execute();
        } catch (AuthorizationException $e) {
            return back()->withErrors(['names' => $e->getMessage()])->withInput();
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['names' => $e->getMessage()])->withInput();
        } catch (VaultHasNoContactDefault) {
            return back()->withErrors(['names' => __('This vault has no template for new contacts to start from. Choose one in the vault settings.')])->withInput();
        }

        $destination = $request->boolean('keep_going')
            ? redirect()->route('contacts.new', $vault)
            : redirect()->route('contacts.show', ['vault' => $vault, 'contact' => $contact]);

        return $destination
            ->with('status', __(':name is written down.', ['name' => $contact->name]))
            ->with('status_description', $contact->is_partial
                ? __('A partial contact stays off the list of people until you make them a full contact from their page.')
                : __('The rest of the page is yours to fill in whenever you know it.'));
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(Request $request): array
    {
        return [
            ...UpdateContactNameRepresentations::rules('names', [], requiresOne: true),
            'maiden_name' => ['nullable', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
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
