<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateLifeEvent;
use App\Actions\DestroyLifeEvent;
use App\Actions\UpdateLifeEvent;
use App\Enums\DateFormatEnum;
use App\Exceptions\FieldIsRequired;
use App\Exceptions\FieldValueIsNotUnique;
use App\Exceptions\LifeEventTypeIsNotAvailable;
use App\Exceptions\NotEnoughRepetitions;
use App\Exceptions\NotEnoughStorageLeft;
use App\Exceptions\TooManyRepetitions;
use App\Helpers\AvailableLocales;
use App\Helpers\FieldValueRules;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Field;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\LifeEventType;
use App\Models\StructureVersion;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ContactLifeEventController extends Controller
{
    public function create(Request $request, Vault $vault, Contact $contact): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $validated = $request->validate($this->rules());

        $lifeEventType = $this->typeFrom($request, $validated);

        $values = $this->valuesFrom($request, $vault, $lifeEventType->structure?->publishedVersion);

        $participants = $this->participantsFrom($vault, $contact, $validated);

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
                participants: $participants->all(),
                values: $values,
            )->execute();
        } catch (LifeEventTypeIsNotAvailable $e) {
            return back()->withInput()->withErrors(['life_event_type_id' => $e->getMessage()]);
        } catch (FieldIsRequired|FieldValueIsNotUnique|NotEnoughRepetitions|NotEnoughStorageLeft|TooManyRepetitions $e) {
            return back()->withInput()->withErrors(['fields' => $e->getMessage()]);
        }

        return redirect()->route('contacts.show', ['vault' => $vault, 'contact' => $contact])
            ->with('status', __(':type, :date, is on the timeline of :names.', $this->sentenceParts($request, $lifeEvent)))
            ->with('lifeEventJustAdded', $lifeEvent->id);
    }

    public function edit(Request $request, Vault $vault, Contact $contact, LifeEvent $lifeEvent): View
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);
        abort_unless($this->takesPart($contact, $lifeEvent), 404);

        return view('app.vaults.contacts.show', [
            'viewModel' => new ContactViewModel(
                user: $request->user(),
                vault: $vault,
                contact: $contact,
                editingLifeEvent: $lifeEvent,
            ),
        ]);
    }

    public function update(Request $request, Vault $vault, Contact $contact, LifeEvent $lifeEvent): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);
        abort_unless($this->takesPart($contact, $lifeEvent), 404);

        $editUrl = route('contacts.lifeEvents.edit', ['vault' => $vault, 'contact' => $contact, 'lifeEvent' => $lifeEvent]);

        try {
            $validated = $request->validate([
                ...$this->rules(),
                'kept_files' => ['nullable', 'array'],
                'kept_files.*' => ['integer'],
            ]);

            $lifeEventType = $this->typeFrom($request, $validated);

            $version = $lifeEventType->id === $lifeEvent->life_event_type_id
                ? $lifeEvent->structureVersion
                : $lifeEventType->structure?->publishedVersion;

            $values = $this->valuesFrom($request, $vault, $version);
        } catch (ValidationException $e) {
            throw $e->redirectTo($editUrl);
        }

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
                participants: $this->participantsFrom($vault, $contact, $validated)->all(),
                values: $values,
                removedFieldIds: $removedFieldIds,
            )->execute();
        } catch (LifeEventTypeIsNotAvailable $e) {
            return redirect()->to($editUrl)->withInput()->withErrors(['life_event_type_id' => $e->getMessage()]);
        } catch (FieldIsRequired|FieldValueIsNotUnique|NotEnoughRepetitions|NotEnoughStorageLeft|TooManyRepetitions $e) {
            return redirect()->to($editUrl)->withInput()->withErrors(['fields' => $e->getMessage()]);
        }

        return redirect()->route('contacts.show', ['vault' => $vault, 'contact' => $contact])
            ->with('status', __('Changes saved. :type, :date, is on the timeline of :names.', $this->sentenceParts($request, $lifeEvent)))
            ->with('lifeEventEdited', $lifeEvent->id);
    }

    public function destroy(Request $request, Vault $vault, Contact $contact, LifeEvent $lifeEvent): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $deletedLine = __(':type, :date, is deleted from the timeline of :names.', $this->sentenceParts($request, $lifeEvent));

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

    /** @return array<string, array<int, string>> */
    private function rules(): array
    {
        return [
            'life_event_type_id' => ['required', 'integer'],
            'happened_on' => ['required', 'date'],
            'participants' => ['nullable', 'array'],
            'participants.*' => ['integer', 'distinct'],
            'title' => ['nullable', 'string', 'max:255'],
            'narrative' => ['nullable', 'string', 'max:65535'],
            'location' => ['nullable', 'string', 'max:255'],
            'duration_in_minutes' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ];
    }

    /** @param  array<string, mixed>  $validated */
    private function typeFrom(Request $request, array $validated): LifeEventType
    {
        return LifeEventType::query()
            ->where('account_id', $request->user()->account_id)
            ->findOrFail($validated['life_event_type_id']);
    }

    /** @return array<int, mixed> */
    private function valuesFrom(Request $request, Vault $vault, ?StructureVersion $version): array
    {
        $fields = $version?->inputFields() ?? new Collection;

        return $request->validate(
            FieldValueRules::for($fields, $vault),
            [],
            $fields->mapWithKeys(fn (Field $field): array => ["fields.{$field->id}" => mb_strtolower($field->label)])->all(),
        )['fields'] ?? [];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return Collection<int, Contact>
     */
    private function participantsFrom(Vault $vault, Contact $contact, array $validated): Collection
    {
        $ids = array_values(array_diff($validated['participants'] ?? [], [$contact->id]));

        return Contact::query()
            ->where('vault_id', $vault->id)
            ->findOrFail($ids)
            ->sortBy(fn (Contact $participant): int|false => array_search($participant->id, $ids, true))
            ->values();
    }

    private function takesPart(Contact $contact, LifeEvent $lifeEvent): bool
    {
        return LifeEventParticipant::query()
            ->where('life_event_id', $lifeEvent->id)
            ->where('participant_type', Contact::class)
            ->where('participant_id', $contact->id)
            ->exists();
    }

    /**
     * The type, the full date and the first names of everybody who was there,
     * for the sentence that says whose timeline the life event is on.
     *
     * @return array{type: string, date: string, names: string}
     */
    private function sentenceParts(Request $request, LifeEvent $lifeEvent): array
    {
        $names = $lifeEvent->participants()
            ->with('participant')
            ->get()
            ->map(fn (LifeEventParticipant $participant): mixed => $participant->participant)
            ->filter(fn (mixed $person): bool => $person instanceof Contact)
            ->map(fn (Contact $person): string => $person->first_name ?? $person->name)
            ->values();

        $format = $request->user()->date_format->value ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];

        return [
            'type' => $lifeEvent->lifeEventType->name,
            'date' => DateFormatEnum::write($lifeEvent->happened_on, $format),
            'names' => $names->count() > 1
                ? __(':names and :last', ['names' => $names->slice(0, -1)->implode(__(', ')), 'last' => $names->last()])
                : (string) $names->first(),
        ];
    }
}
