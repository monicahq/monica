<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\SetFieldValues;
use App\Exceptions\FieldIsRequired;
use App\Exceptions\FieldValueIsNotUnique;
use App\Exceptions\NotEnoughRepetitions;
use App\Exceptions\NotEnoughStorageLeft;
use App\Exceptions\TooManyRepetitions;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveFieldValuesRequest;
use App\Models\Contact;
use App\Models\TemplateVersionSection;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * One block of a record, opened and written on its own.
 *
 * The record reads before it edits, and the unit of editing is the section: the
 * block somebody opens is the block they are given controls for, and saving it
 * writes that block and leaves the rest of the page exactly as it was.
 *
 * Which block is open is part of the address rather than a flag on the client,
 * for two reasons. It means cancelling asks for the record again and therefore
 * genuinely throws away what was typed, instead of hiding a form that still
 * holds it. And it means the whole thing works as three ordinary requests, with
 * the interface merely aiming each of them at the one panel it concerns.
 *
 * The page is deliberately not in the address. A section already knows which
 * page it sits on, and naming it twice is a way for the two halves to disagree.
 */
class ContactSectionController extends Controller
{
    /**
     * The block, open, on the record it belongs to.
     *
     * It answers with the whole screen rather than the panel alone. The response
     * has to be a page somebody can land on, bookmark and reload; the interface
     * takes the one region it asked for out of it, which is the same bargain
     * every other screen in the product makes.
     */
    public function edit(Request $request, Vault $vault, Contact $contact, TemplateVersionSection $templateVersionSection): View
    {
        $this->refuseWhatIsNotReachable($request, $vault, $contact, $templateVersionSection);

        return view('app.vaults.contacts.show', [
            'viewModel' => new ContactViewModel(
                user: $request->user(),
                vault: $vault,
                contact: $contact,
                selectedPageKey: $templateVersionSection->templateVersionPage->key,
                editingSection: $templateVersionSection,
            ),
        ]);
    }

    /**
     * Write the block: everything it asks about, at once.
     *
     * The refusals that reach here rather than the validator are the ones that
     * are not questions about the input. Whether another record in the vault
     * already holds a value the field says only one may hold is a question about
     * the vault; how many repetitions are left is a question about what the
     * record already holds; and whether the account has room for the file is a
     * question about the account. All of them send the block back open, since a
     * refusal somebody cannot see the controls of is a refusal they cannot act
     * on.
     */
    public function update(SaveFieldValuesRequest $request, Vault $vault, Contact $contact, TemplateVersionSection $templateVersionSection): RedirectResponse
    {
        $this->refuseWhatIsNotReachable($request, $vault, $contact, $templateVersionSection);

        try {
            new SetFieldValues(
                user: $request->user(),
                record: $contact,
                section: $templateVersionSection,
                values: $request->validated('fields', []),
                yearIsUnknown: $request->validated('year_is_unknown', []),
                monthIsUnknown: $request->validated('month_is_unknown', []),
            )->execute();
        } catch (FieldIsRequired|FieldValueIsNotUnique|NotEnoughRepetitions|NotEnoughStorageLeft|TooManyRepetitions $e) {
            return redirect()
                ->route('contacts.sections.edit', ['vault' => $vault, 'contact' => $contact, 'templateVersionSection' => $templateVersionSection])
                ->withInput()
                ->withErrors(['fields' => $e->getMessage()]);
        }

        return redirect()
            ->route('contacts.show', [
                'vault' => $vault,
                'contact' => $contact,
                'page' => $templateVersionSection->templateVersionPage->key,
            ])
            ->with('status', __('Saved.'))
            ->with('status_description', __(':section is up to date.', ['section' => $this->nameOf($templateVersionSection)]));
    }

    /**
     * Neither half of the address can be trusted on its own. The contact has to
     * be one this person reaches through this vault, and the section has to be
     * one the contact's own structure carries: without the second check somebody
     * could save a block of one of their templates onto a record rendering from
     * another.
     */
    private function refuseWhatIsNotReachable(Request $request, Vault $vault, Contact $contact, TemplateVersionSection $templateVersionSection): void
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);
        abort_unless($templateVersionSection->template_version_id === $contact->template_version_id, 404);
    }

    private function nameOf(TemplateVersionSection $templateVersionSection): string
    {
        return $templateVersionSection->title_override ?? $templateVersionSection->sectionVersion->section->name;
    }
}
