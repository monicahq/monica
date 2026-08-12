<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateContact;
use App\Actions\DestroyContact;
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
     * The two things about a contact the template has no say over: what they are
     * called, and whether they are on the record at all.
     *
     * They share a screen because they are the same errand. The record offers one
     * way in rather than a button for each, since a page of a person is about the
     * person and not about the three things that can be done to the row.
     */
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
     * Take somebody off the list, which is a soft deletion: nothing they took
     * part in is destroyed, and they can be brought back.
     *
     * The refusal is not a question about the input, so it is not the validator's.
     * The contact representing whoever is looking cannot go, and the screen does
     * not offer the button for it; this is what answers somebody who asked
     * anyway.
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
            templateVersion: $this->versionAskedFor($request),
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
     *
     * It is read off the request rather than out of the validated array, because
     * `integer` is a rule about what arrived rather than a cast: a form sends
     * "2" and the validator hands back the same string. Validation has already
     * run, so what is read here is what passed it.
     */
    private function versionAskedFor(Request $request): ?TemplateVersion
    {
        if (! $request->filled('template_version_id')) {
            return null;
        }

        return TemplateVersion::query()->find($request->integer('template_version_id'));
    }

    private function nameOrderAskedFor(?string $order): ?NameOrderEnum
    {
        if ($order === null || $order === '') {
            return null;
        }

        return NameOrderEnum::from($order);
    }
}
