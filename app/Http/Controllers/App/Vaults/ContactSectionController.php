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
 * One block of a record, opened and written on its own. Saving writes that
 * block and leaves the rest of the page exactly as it was.
 *
 * Which block is open is part of the address rather than a flag on the
 * client, so cancelling asks for the record again and genuinely throws away
 * what was typed. The page is deliberately not in the address: a section
 * already knows which page it sits on, and naming it twice is a way for the
 * two halves to disagree.
 */
class ContactSectionController extends Controller
{
    /**
     * It answers with the whole screen rather than the panel alone: the
     * response has to be a page somebody can land on, bookmark and reload,
     * and the interface takes the one region it asked for out of it.
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
     * The refusals that reach here rather than the validator are the ones
     * that are not questions about the input: uniqueness is a question about
     * the vault, repetitions about what the record already holds, and storage
     * about the account. All send the block back open, since a refusal
     * somebody cannot see the controls of is one they cannot act on.
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
     * Neither half of the address can be trusted on its own. Without the
     * second check somebody could save a block of one of their templates onto
     * a record rendering from another.
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
