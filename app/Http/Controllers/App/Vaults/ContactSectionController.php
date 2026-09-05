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
use App\Models\TemplateVersionSection;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactSectionController extends Controller
{
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
                reminders: $request->validated('reminders', []),
            )->execute();
        } catch (FieldIsRequired|FieldValueIsNotUnique|NotEnoughRepetitions|NotEnoughStorageLeft|ReminderIsNotValid|ReminderMomentIsNotValid|ReminderNeedsADate|TooManyRepetitions|ValueAlreadyHasAReminder $e) {
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
