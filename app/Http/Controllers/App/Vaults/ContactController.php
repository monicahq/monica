<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateContact;
use App\Enums\NameOrderEnum;
use App\Enums\TemplateAppliesToEnum;
use App\Http\Controllers\Controller;
use App\Models\TemplateVersion;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactFormViewModel;
use App\ViewModels\Vaults\ContactsViewModel;
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
     * have just written down is: their own page is a later screen, and the list
     * is the nearest thing to seeing that it worked.
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
