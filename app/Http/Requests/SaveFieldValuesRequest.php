<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderMomentDirectionEnum;
use App\Enums\ReminderMomentUnitEnum;
use App\Helpers\FieldValueRules;
use App\Models\Field;
use App\Models\Reminder;
use App\Models\TemplateVersionSection;
use App\Rules\ReminderMoments;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Override;
use RuntimeException;

class SaveFieldValuesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return array_merge([
            'fields' => ['array'],
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
            'reminders.*.moments' => ['nullable', 'array'],
            'reminders.*.moments.*.direction' => ['nullable', Rule::in(ReminderMomentDirectionEnum::values())],
            'reminders.*.moments.*.amount' => ['nullable', 'integer'],
            'reminders.*.moments.*.unit' => ['nullable', Rule::in(ReminderMomentUnitEnum::values())],
        ], FieldValueRules::for($this->fieldsInTheSection()));
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach ((array) $this->input('reminders', []) as $fieldId => $reminder) {
                    if (! filter_var($reminder['on'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                        continue;
                    }

                    $problem = ReminderMoments::problemWith($reminder['moments'] ?? []);

                    if ($problem !== null) {
                        $validator->errors()->add("reminders.{$fieldId}.moments", $problem);
                    }
                }
            },
        ];
    }

    /** @return array<string, string> */
    #[Override]
    public function attributes(): array
    {
        $names = [];

        foreach ($this->fieldsInTheSection() as $field) {
            $names["fields.{$field->id}"] = mb_strtolower($field->label);

            foreach ($field->children as $child) {
                $names["fields.{$field->id}.*.values.{$child->id}"] = mb_strtolower($child->label);
            }
        }

        return $names;
    }

    #[Override]
    protected function getRedirectUrl(): string
    {
        return route('contacts.sections.edit', [
            'vault' => $this->route('vault'),
            'contact' => $this->route('contact'),
            'templateVersionSection' => $this->section(),
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

    public function section(): TemplateVersionSection
    {
        $section = $this->route('templateVersionSection');

        return $section instanceof TemplateVersionSection
            ? $section
            : throw new RuntimeException('This request has no section.');
    }
}
