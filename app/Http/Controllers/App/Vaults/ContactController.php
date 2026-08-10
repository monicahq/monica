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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactController extends Controller
{
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
     * leave. Everybody else goes back to the vault, since the contact's own page
     * is a later screen and there is nowhere else to send them yet.
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
            : redirect()->route('vaults.show', $vault);

        return $destination
            ->with('status', __(':name is written down.', ['name' => $contact->name]))
            ->with('status_description', __('The rest of the page is yours to fill in whenever you know it.'));
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
