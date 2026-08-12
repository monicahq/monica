<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateContact;
use App\Actions\SetFieldValues;
use App\Enums\NameOrderEnum;
use App\Enums\TemplateAppliesToEnum;
use App\Exceptions\FieldIsRequired;
use App\Exceptions\FieldValueIsNotUnique;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveFieldValuesRequest;
use App\Models\Contact;
use App\Models\TemplateVersion;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactFormViewModel;
use App\ViewModels\Vaults\ContactsViewModel;
use App\ViewModels\Vaults\ContactViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactController extends Controller
{
    /**
     * Everybody in one vault, on one list. Everything the screen offers goes
     * through the URL, so a narrowed list can be linked to and comes back the
     * same way after a reload.
     *
     * Which people are shown is a segment of the address rather than a query
     * string: the favourites and the archived are three places somebody goes,
     * not three settings they carry. What they typed and what they sorted on
     * stay in the query string, since those are parameters of a place.
     */
    public function index(Request $request, Vault $vault, ?string $filter = null): View
    {
        return view('app.vaults.contacts.index', [
            'viewModel' => new ContactsViewModel(
                user: $request->user(),
                vault: $vault,
                query: (string) $request->string('query'),
                filter: $filter ?? ContactsViewModel::FILTER_ALL,
                sort: (string) $request->string('sort', ContactsViewModel::SORT_NAME),
                direction: (string) $request->string('direction', ContactsViewModel::DIRECTION_ASC),
            ),
        ]);
    }

    /**
     * One person, and everything written down about them.
     *
     * Which page of the record is open travels in the address, so a tab can be
     * linked to and the back button walks between them. It is the page's key
     * rather than the page itself: a key belongs to one version of one
     * template, so two versions can both have a page called "Health" and
     * binding the row would need the version to say which.
     */
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

    /**
     * Save one page of the record: everything the template asks on it, at once.
     *
     * A page is what somebody fills in, and it is also the only thing that can
     * refuse a required field, so the whole of it is written or none of it is.
     *
     * Two of the refusals reach here rather than the validator, because neither
     * is a question about the input. Whether another record in the vault
     * already holds a value the field says only one may hold is a question
     * about the vault, and it is asked where the vault is in hand. They land on
     * the form as a sentence rather than under a control, since the control
     * they belong to is not always the one somebody just typed in.
     */
    public function update(SaveFieldValuesRequest $request, Vault $vault, Contact $contact, ?string $page = null): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $templatePage = $request->page();

        abort_if($templatePage === null, 404);

        try {
            new SetFieldValues(
                user: $request->user(),
                record: $contact,
                page: $templatePage,
                values: $request->validated('fields', []),
                yearIsUnknown: $request->validated('year_is_unknown', []),
                monthIsUnknown: $request->validated('month_is_unknown', []),
            )->execute();
        } catch (FieldIsRequired|FieldValueIsNotUnique $e) {
            return redirect()
                ->route('contacts.show', ['vault' => $vault, 'contact' => $contact, 'page' => $page])
                ->withInput()
                ->withErrors(['fields' => $e->getMessage()]);
        }

        return redirect()
            ->route('contacts.show', ['vault' => $vault, 'contact' => $contact, 'page' => $page])
            ->with('status', __('Saved.'))
            ->with('status_description', __(':page is up to date.', ['page' => $templatePage->label]));
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
     * Write the contact down, then decide where whoever wrote it goes next.
     *
     * Somebody working through a list of people has said so by ticking the box,
     * and is handed the empty form again rather than a screen they have to
     * leave. Everybody else lands on the list of contacts, where the person they
     * have just written down now is. Their own page exists and is deliberately
     * not where this goes: it holds only what a form has just been filled in
     * with, so it would read as the same screen again with fewer places to type.
     *
     * A partial contact is the exception the message has to cover. They are
     * written down and then kept off that list until somebody fills them in, so
     * without a word about it the screen says "written down" and then shows a
     * list they are not on.
     */
    public function create(Request $request, Vault $vault): RedirectResponse
    {
        $validated = $request->validate($this->rules($request));

        $contact = new CreateContact(
            user: $request->user(),
            vault: $vault,
            templateVersion: $this->versionAskedFor($validated['template_version_id'] ?? null),
            prefix: $validated['prefix'] ?? null,
            firstName: $validated['first_name'],
            middleName: $validated['middle_name'] ?? null,
            lastName: $validated['last_name'] ?? null,
            suffix: $validated['suffix'] ?? null,
            maidenName: $validated['maiden_name'] ?? null,
            nickname: $validated['nickname'] ?? null,
            nameOrder: $this->nameOrderAskedFor($validated['name_order'] ?? null),
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
     * The template is offered as the published version of one of the account's
     * contact templates, which is exactly what a contact stores, so the rule
     * asks the templates table for it rather than trusting the select.
     *
     * Only the first name is required. Somebody met once is worth writing down
     * with nothing else, and a form that refuses that is a form people work
     * around.
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
     * The version the contact lands on, which is nothing at all when the screen
     * offered no choice: the action reads the vault's default in that case, and
     * naming it here would only repeat the same lookup.
     */
    private function versionAskedFor(?int $versionId): ?TemplateVersion
    {
        if ($versionId === null) {
            return null;
        }

        return TemplateVersion::query()->find($versionId);
    }

    private function nameOrderAskedFor(?string $order): ?NameOrderEnum
    {
        if ($order === null || $order === '') {
            return null;
        }

        return NameOrderEnum::from($order);
    }
}
