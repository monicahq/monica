<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateLifeEvent;
use App\Actions\DestroyLifeEvent;
use App\Actions\UpdateLifeEvent;
use App\Exceptions\FieldIsRequired;
use App\Exceptions\FieldValueIsNotUnique;
use App\Exceptions\LifeEventTypeIsNotAvailable;
use App\Exceptions\NotEnoughRepetitions;
use App\Exceptions\NotEnoughStorageLeft;
use App\Exceptions\TooManyRepetitions;
use App\Helpers\AlpineAjaxTargets;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveLifeEventRequest;
use App\Models\Contact;
use App\Models\Field;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactLifeEventController extends Controller
{
    public function new(Request $request, Vault $vault, Contact $contact): View
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $viewModel = new ContactViewModel(
            user: $request->user(),
            vault: $vault,
            contact: $contact,
            opensLifeEventPanel: true,
        );

        $targets = AlpineAjaxTargets::from($request);

        return view($viewModel->drawsTargetsAlone($targets) ? 'app.vaults.contacts.panels' : 'app.vaults.contacts.show', [
            'viewModel' => $viewModel,
            'targets' => $targets,
        ]);
    }

    public function create(SaveLifeEventRequest $request, Vault $vault, Contact $contact): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $validated = $request->validated();

        $lifeEventType = $request->lifeEventType();

        $values = $request->values($lifeEventType->structure?->publishedVersion);

        try {
            $lifeEvent = new CreateLifeEvent(
                user: $request->user(),
                vault: $vault,
                contact: $contact,
                lifeEventType: $lifeEventType,
                happenedOn: CarbonImmutable::parse($validated['happened_on']),
                title: $validated['title'] ?? null,
                narrative: $validated['narrative'] ?? null,
                location: $validated['location'] ?? null,
                durationInMinutes: isset($validated['duration_in_minutes']) ? (int) $validated['duration_in_minutes'] : null,
                participants: $request->participants($contact)->all(),
                values: $values,
            )->execute();
        } catch (LifeEventTypeIsNotAvailable $e) {
            return back()->withInput()->withErrors(['life_event_type_id' => $e->getMessage()]);
        } catch (FieldIsRequired|FieldValueIsNotUnique|NotEnoughRepetitions|NotEnoughStorageLeft|TooManyRepetitions $e) {
            return back()->withInput()->withErrors(['fields' => $e->getMessage()]);
        }

        return redirect()->route('contacts.show', ['vault' => $vault, 'contact' => $contact])
            ->with('status', __(':type, :date, is on the timeline of :names.', $lifeEvent->confirmationParts($request->user())))
            ->with('lifeEventJustAdded', $lifeEvent->id);
    }

    public function edit(Request $request, Vault $vault, Contact $contact, LifeEvent $lifeEvent): View
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);
        abort_unless($this->takesPart($contact, $lifeEvent), 404);

        $viewModel = new ContactViewModel(
            user: $request->user(),
            vault: $vault,
            contact: $contact,
            editingLifeEvent: $lifeEvent,
        );

        $targets = AlpineAjaxTargets::from($request);

        return view($viewModel->drawsTargetsAlone($targets) ? 'app.vaults.contacts.panels' : 'app.vaults.contacts.show', [
            'viewModel' => $viewModel,
            'targets' => $targets,
        ]);
    }

    public function update(SaveLifeEventRequest $request, Vault $vault, Contact $contact, LifeEvent $lifeEvent): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);
        abort_unless($this->takesPart($contact, $lifeEvent), 404);

        $editUrl = route('contacts.lifeEvents.edit', ['vault' => $vault, 'contact' => $contact, 'lifeEvent' => $lifeEvent]);

        $validated = $request->validated();

        $lifeEventType = $request->lifeEventType();

        $version = $lifeEventType->id === $lifeEvent->life_event_type_id
            ? $lifeEvent->structureVersion
            : $lifeEventType->structure?->publishedVersion;

        $values = $request->values($version);

        $removedFieldIds = ($version?->inputFields() ?? new Collection)
            ->filter(fn (Field $field): bool => $field->type->holdsAFile())
            ->reject(fn (Field $field): bool => in_array($field->id, array_map(intval(...), $validated['kept_files'] ?? []), true))
            ->reject(fn (Field $field): bool => $request->hasFile("fields.{$field->id}"))
            ->pluck('id')
            ->all();

        try {
            $lifeEvent = new UpdateLifeEvent(
                user: $request->user(),
                vault: $vault,
                contact: $contact,
                lifeEvent: $lifeEvent,
                lifeEventType: $lifeEventType,
                happenedOn: CarbonImmutable::parse($validated['happened_on']),
                title: $validated['title'] ?? null,
                narrative: $validated['narrative'] ?? null,
                location: $validated['location'] ?? null,
                durationInMinutes: isset($validated['duration_in_minutes']) ? (int) $validated['duration_in_minutes'] : null,
                participants: $request->participants($contact)->all(),
                values: $values,
                removedFieldIds: $removedFieldIds,
            )->execute();
        } catch (LifeEventTypeIsNotAvailable $e) {
            return redirect()->to($editUrl)->withInput()->withErrors(['life_event_type_id' => $e->getMessage()]);
        } catch (FieldIsRequired|FieldValueIsNotUnique|NotEnoughRepetitions|NotEnoughStorageLeft|TooManyRepetitions $e) {
            return redirect()->to($editUrl)->withInput()->withErrors(['fields' => $e->getMessage()]);
        }

        return redirect()->route('contacts.show', ['vault' => $vault, 'contact' => $contact])
            ->with('status', __('Changes saved. :type, :date, is on the timeline of :names.', $lifeEvent->confirmationParts($request->user())))
            ->with('lifeEventEdited', $lifeEvent->id);
    }

    public function destroy(Request $request, Vault $vault, Contact $contact, LifeEvent $lifeEvent): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $deletedLine = __(':type, :date, is deleted from the timeline of :names.', $lifeEvent->confirmationParts($request->user()));

        new DestroyLifeEvent(
            user: $request->user(),
            vault: $vault,
            contact: $contact,
            lifeEvent: $lifeEvent,
        )->execute();

        return redirect()->route('contacts.show', ['vault' => $vault, 'contact' => $contact])
            ->with('status', $deletedLine)
            ->with('lifeEventDeleted', $lifeEvent->uuid)
            ->with('lifeEventDeletedLine', $deletedLine);
    }

    private function takesPart(Contact $contact, LifeEvent $lifeEvent): bool
    {
        return LifeEventParticipant::query()
            ->where('life_event_id', $lifeEvent->id)
            ->where('participant_type', Contact::class)
            ->where('participant_id', $contact->id)
            ->exists();
    }
}
