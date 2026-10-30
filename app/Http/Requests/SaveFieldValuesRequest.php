<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\FieldTypeEnum;
use App\Enums\ReminderCadenceEnum;
use App\Helpers\FieldValueFormatter;
use App\Helpers\FieldValueRules;
use App\Models\Field;
use App\Models\Reminder;
use App\Models\StructureVersionSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Override;
use RuntimeException;

class SaveFieldValuesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The date panel asks how often in one list, `once` or a cadence and its interval such as
     * `yearly:2`, and the action reads them as three answers. A value that is neither is left as it
     * is, so the rule on `recurs` refuses it.
     */
    #[Override]
    protected function prepareForValidation(): void
    {
        $reminders = $this->input('reminders');

        if (! is_array($reminders)) {
            return;
        }

        foreach ($reminders as $fieldId => $reminder) {
            $repeat = is_array($reminder) ? ($reminder['repeat'] ?? null) : null;

            if (! is_string($repeat)) {
                continue;
            }

            if ($repeat === 'once') {
                $reminders[$fieldId] = [...$reminder, 'recurs' => '0'];

                continue;
            }

            [$cadence, $interval] = array_pad(explode(':', $repeat, 2), 2, null);

            $reminders[$fieldId] = [...$reminder, 'recurs' => '1', 'cadence' => $cadence, 'interval' => $interval];
        }

        $this->merge(['reminders' => $reminders]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return array_merge([
            'fields' => ['array'],
            'labels' => ['array'],
            'label_wordings' => ['array'],
            'new_choices' => ['array'],
            'year_is_unknown' => ['array'],
            'month_is_unknown' => ['array'],
            'reminders' => ['array'],
            'reminders.*.on' => ['nullable', 'boolean'],
            'reminders.*.name' => ['nullable', 'string', 'max:255'],
            'reminders.*.recurs' => ['nullable', 'boolean'],
            'reminders.*.cadence' => ['nullable', Rule::in(ReminderCadenceEnum::values())],
            'reminders.*.interval' => ['nullable', 'integer', 'min:1', 'max:'.Reminder::MAXIMUM_INTERVAL],
            'reminders.*.time' => ['nullable', 'date_format:H:i'],
            'reminders.*.timezone' => ['nullable', 'string', 'timezone'],
        ], FieldValueRules::for($this->fieldsInTheSection(), $this->route('vault')));
    }

    /** @return array<string, string> */
    #[Override]
    public function attributes(): array
    {
        $names = [];

        foreach ($this->fieldsInTheSection() as $field) {
            $names["fields.{$field->id}"] = mb_strtolower($field->label);
            $names["labels.{$field->id}"] = mb_strtolower($field->label);
            $names["new_choices.{$field->id}.*"] = mb_strtolower($field->label);
            $names = array_merge($names, $this->addressPartsOf($field, "fields.{$field->id}"));

            foreach ($field->children as $child) {
                $names["fields.{$field->id}.*.values.{$child->id}"] = mb_strtolower($child->label);
                $names["fields.{$field->id}.*.labels.{$child->id}"] = mb_strtolower($child->label);
                $names = array_merge($names, $this->addressPartsOf($child, "fields.{$field->id}.*.values.{$child->id}"));
            }
        }

        return $names;
    }

    /** @return array<string, string> */
    private function addressPartsOf(Field $field, string $prefix): array
    {
        if ($field->type !== FieldTypeEnum::Address) {
            return [];
        }

        $names = [];

        foreach (FieldValueFormatter::addressPartNames() as $part => $partName) {
            $names["{$prefix}.{$part}"] = __(':part of :field', [
                'part' => mb_strtolower($partName),
                'field' => mb_strtolower($field->label),
            ]);
        }

        return $names;
    }

    #[Override]
    protected function getRedirectUrl(): string
    {
        return route('contacts.sections.edit', [
            'vault' => $this->route('vault'),
            'contact' => $this->route('contact'),
            'structureVersionSection' => $this->section(),
        ]);
    }

    /** @return Collection<int, Field> */
    public function fieldsInTheSection(): Collection
    {
        return $this->section()
            ->sectionVersion
            ->fields
            ->whereNull('parent_field_id')
            ->values();
    }

    public function section(): StructureVersionSection
    {
        $section = $this->route('structureVersionSection');

        return $section instanceof StructureVersionSection
            ? $section
            : throw new RuntimeException('This request has no section.');
    }
}
