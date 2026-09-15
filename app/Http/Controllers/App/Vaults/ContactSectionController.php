<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\SetFieldValues;
use App\Exceptions\FieldIsRequired;
use App\Exceptions\FieldValueIsNotUnique;
use App\Exceptions\NotEnoughRepetitions;
use App\Exceptions\NotEnoughStorageLeft;
use App\Exceptions\ReminderIsNotValid;
use App\Exceptions\ReminderMomentIsNotValid;
use App\Exceptions\ReminderNeedsADate;
use App\Exceptions\TooManyRepetitions;
use App\Exceptions\ValueAlreadyHasAReminder;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveFieldValuesRequest;
use App\Models\Contact;
use App\Models\StructureVersionSection;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactSectionController extends Controller
{
    public function edit(Request $request, Vault $vault, Contact $contact, StructureVersionSection $structureVersionSection): View
    {
        $this->refuseWhatIsNotReachable($request, $vault, $contact, $structureVersionSection);

        return view('app.vaults.contacts.show', [
            'viewModel' => new ContactViewModel(
                user: $request->user(),
                vault: $vault,
                contact: $contact,
                selectedPageKey: $structureVersionSection->structureVersionPage->key,
                editingSection: $structureVersionSection,
            ),
        ]);
    }

    public function update(SaveFieldValuesRequest $request, Vault $vault, Contact $contact, StructureVersionSection $structureVersionSection): RedirectResponse
    {
        $this->refuseWhatIsNotReachable($request, $vault, $contact, $structureVersionSection);

        try {
            new SetFieldValues(
                user: $request->user(),
                record: $contact,
                section: $structureVersionSection,
                values: $request->validated('fields', []),
                yearIsUnknown: $request->validated('year_is_unknown', []),
                monthIsUnknown: $request->validated('month_is_unknown', []),
                reminders: $request->validated('reminders', []),
            )->execute();
        } catch (FieldIsRequired|FieldValueIsNotUnique|NotEnoughRepetitions|NotEnoughStorageLeft|ReminderIsNotValid|ReminderMomentIsNotValid|ReminderNeedsADate|TooManyRepetitions|ValueAlreadyHasAReminder $e) {
            return redirect()
                ->route('contacts.sections.edit', ['vault' => $vault, 'contact' => $contact, 'structureVersionSection' => $structureVersionSection])
                ->withInput()
                ->withErrors(['fields' => $e->getMessage()]);
        }

        return redirect()
            ->route('contacts.show', [
                'vault' => $vault,
                'contact' => $contact,
                'page' => $structureVersionSection->structureVersionPage->key,
            ])
            ->with('status', __('Saved.'))
            ->with('status_description', __(':section is up to date.', ['section' => $this->nameOf($structureVersionSection)]));
    }

    private function refuseWhatIsNotReachable(Request $request, Vault $vault, Contact $contact, StructureVersionSection $structureVersionSection): void
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);
        abort_unless($structureVersionSection->structure_version_id === $contact->structure_version_id, 404);
    }

    private function nameOf(StructureVersionSection $structureVersionSection): string
    {
        return $structureVersionSection->title_override ?? $structureVersionSection->sectionVersion->section->name;
    }
}
