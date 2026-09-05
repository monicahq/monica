<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateContact;
use App\Actions\DestroyContact;
use App\Enums\ContactGroupingEnum;
use App\Enums\ContactSortEnum;
use App\Enums\NameOrderEnum;
use App\Enums\TemplateAppliesToEnum;
use App\Exceptions\CannotDeleteTheMeContact;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\TemplateVersion;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactEditViewModel;
use App\ViewModels\Vaults\ContactFormViewModel;
use App\ViewModels\Vaults\ContactsViewModel;
use App\ViewModels\Vaults\ContactViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(Request $request, Vault $vault, ?string $filter = null): View
    {
        return view('app.vaults.contacts.index', [
            'viewModel' => new ContactsViewModel(
                user: $request->user(),
                vault: $vault,
                query: (string) $request->string('query'),
                filter: $filter ?? ContactGroupingEnum::Everyone->value,
                sort: (string) $request->string('sort', ContactSortEnum::Name->value),
                direction: (string) $request->string('direction', ContactsViewModel::DIRECTION_ASC),
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

    /**
     * The contact representing whoever is looking cannot go. The screen does not
     * offer the button, and the refusal is not a question about the input, so it
     * is caught here rather than in the validator.
     */
    public function destroy(Request $request, Vault $vault, Contact $contact): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $name = $contact->name;

        try {
            new DestroyContact(user: $request->user(), contact: $contact)->execute();
        } catch (CannotDeleteTheMeContact $e) {
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

    /**
     * Somebody working through a list of people ticks the box and is handed the
     * empty form again. Everybody else lands on the list of contacts, and never
     * on the new record: it holds only what the form was just filled in with, so
     * it would read as the same screen with fewer places to type.
     *
     * A partial contact is kept off that list until somebody fills them in, so
     * the message has to say so or the screen shows a list they are not on.
     */
    public function create(Request $request, Vault $vault): RedirectResponse
    {
        $validated = $request->validate($this->rules($request));
        $nameOrder = $validated['name_order'] ?? null;

        $contact = new CreateContact(
            user: $request->user(),
            vault: $vault,
            templateVersion: $this->versionAskedFor($request),
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

        $destination = $request->boolean('keep_going')
            ? redirect()->route('contacts.new', $vault)
            : redirect()->route('contacts.index', $vault);

        return $destination
            ->with('status', __(':name is written down.', ['name' => $contact->name]))
            ->with('status_description', $contact->is_partial
                ? __('A partial contact stays off the list of contacts until they are more than a first name.')
                : __('The rest of the page is yours to fill in whenever you know it.'));
    }

    /**
     * The template rule asks the templates table rather than trusting the
     * select, since what a contact stores is the published version of one of
     * the account's own contact templates.
     *
     * Only the first name is required: somebody met once is worth writing down
     * with nothing else, and a form that refuses that is one people work around.
     *
     * @return array<string, array<int, mixed>>
     */
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
            'template_version_id' => [
                'nullable',
                'integer',
                Rule::exists('templates', 'published_version_id')
                    ->where('account_id', $request->user()->account_id)
                    ->where('applies_to', TemplateAppliesToEnum::Contact->value)
                    ->where('is_active', true),
            ],
        ];
    }

    /**
     * Nothing at all when the screen offered no choice: the action reads the
     * vault's default in that case.
     *
     * It is read off the request rather than out of the validated array because
     * `integer` is a rule about what arrived rather than a cast, and validation
     * has already run.
     */
    private function versionAskedFor(Request $request): ?TemplateVersion
    {
        if (! $request->filled('template_version_id')) {
            return null;
        }

        return TemplateVersion::query()->find($request->integer('template_version_id'));
    }
}
