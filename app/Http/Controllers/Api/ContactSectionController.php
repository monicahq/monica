<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\SetFieldValues;
use App\Enums\ReminderCadenceEnum;
use App\Exceptions\FieldIsRequired;
use App\Exceptions\FieldValueIsNotUnique;
use App\Exceptions\NotEnoughRepetitions;
use App\Exceptions\NotEnoughStorageLeft;
use App\Exceptions\ReminderIsNotValid;
use App\Exceptions\ReminderNeedsADate;
use App\Exceptions\TooManyRepetitions;
use App\Exceptions\ValueAlreadyHasAReminder;
use App\Exceptions\ValueListItemIsStopped;
use App\Helpers\FieldValueRules;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use App\Models\Field;
use App\Models\Reminder;
use App\Models\StructureVersionSection;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ContactSectionController extends Controller
{
    public function update(Request $request, string $vault, string $contact, string $section): JsonResponse
    {
        $vault = $request->user()->account->vaults()->addressedBy($vault)->firstOrFail();
        $contact = Contact::query()->where('vault_id', $vault->id)->addressedBy($contact)->firstOrFail();
        $section = StructureVersionSection::query()
            ->where('structure_version_id', $contact->structure_version_id)
            ->whereKey($section)
            ->firstOrFail();

        $fields = $section->sectionVersion->fields
            ->whereNull('parent_field_id')
            ->filter(fn (Field $field): bool => $field->type->hasAnInput())
            ->values();

        $fieldRules = FieldValueRules::withoutFiles($fields, $vault);

        foreach ($fields as $field) {
            $fieldRules["fields.{$field->id}"] = ['sometimes', ...$fieldRules["fields.{$field->id}"] ?? []];
        }

        $dateFieldIds = $fields
            ->filter(fn (Field $field): bool => $field->type->canDriveAReminder())
            ->pluck('id')
            ->all();

        $validated = $request->validate(
            array_merge([
                'fields' => ['array'],
                'labels' => ['array'],
                'label_wordings' => ['array'],
                'year_is_unknown' => ['array'],
                'month_is_unknown' => ['array'],
                'new_choices' => ['array'],
                'reminders' => $dateFieldIds === [] ? ['prohibited'] : ['array:'.implode(',', $dateFieldIds)],
                'reminders.*' => ['nullable', 'array'],
                'reminders.*.name' => ['nullable', 'string', 'max:255'],
                'reminders.*.cadence' => ['nullable', Rule::in(ReminderCadenceEnum::values())],
                'reminders.*.interval' => ['nullable', 'integer', 'min:1', 'max:'.Reminder::MAXIMUM_INTERVAL],
                'reminders.*.time' => ['nullable', 'date_format:H:i'],
                'reminders.*.timezone' => ['nullable', 'string', 'timezone'],
            ], $fieldRules),
            [],
            $fields->mapWithKeys(fn (Field $field): array => ["fields.{$field->id}" => mb_strtolower($field->label)])->all(),
        );

        $values = $validated['fields'] ?? [];

        $keptFieldIds = $fields
            ->reject(fn (Field $field): bool => array_key_exists($field->id, $values) || array_key_exists($field->id, $validated['new_choices'] ?? []))
            ->pluck('id')
            ->all();

        $reminders = [];

        foreach ($validated['reminders'] ?? [] as $fieldId => $reminder) {
            $cadence = ReminderCadenceEnum::tryFrom((string) ($reminder['cadence'] ?? '')) ?? ReminderCadenceEnum::Yearly;

            $reminders[$fieldId] = $reminder === null ? ['on' => '0'] : [
                'on' => '1',
                'name' => $reminder['name'] ?? null,
                'recurs' => $cadence->recurs() ? '1' : '0',
                'cadence' => $cadence->value,
                'interval' => $reminder['interval'] ?? null,
                'time' => $reminder['time'] ?? null,
                'timezone' => $reminder['timezone'] ?? null,
            ];
        }

        try {
            new SetFieldValues(
                user: $request->user(),
                record: $contact,
                section: $section,
                values: $values,
                labels: $validated['labels'] ?? [],
                labelWordings: $validated['label_wordings'] ?? [],
                yearIsUnknown: $validated['year_is_unknown'] ?? [],
                monthIsUnknown: $validated['month_is_unknown'] ?? [],
                reminders: $reminders,
                keptFieldIds: $keptFieldIds,
                newChoices: $validated['new_choices'] ?? [],
                keptReminderFieldIds: array_values(array_diff($dateFieldIds, array_keys($reminders))),
            )->execute();
        } catch (AuthorizationException|FieldIsRequired|FieldValueIsNotUnique|NotEnoughRepetitions|NotEnoughStorageLeft|ReminderIsNotValid|ReminderNeedsADate|TooManyRepetitions|ValueAlreadyHasAReminder|ValueListItemIsStopped $e) {
            throw ValidationException::withMessages(['fields' => $e->getMessage()]);
        }

        return new ContactResource(
            $contact->refresh()->load(['vault', 'structureVersion.structure.owner', 'avatar', 'nameRepresentations']),
        )->response()->setStatusCode(200);
    }
}
