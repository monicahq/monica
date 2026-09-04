<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderMomentDirectionEnum;
use App\Enums\ReminderMomentUnitEnum;
use App\Helpers\FieldValueRules;
use App\Models\Contact;
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

/**
 * What one section of a record may be saved with.
 *
 * The rules cannot be written down here, because the questions are whatever
 * somebody invented in the template builder. They are read off the fields the
 * section carries, by {@see FieldValueRules}, so the interface and the API
 * enforce the same thing by running the same code.
 *
 * The rules stop at what a form can sensibly say under an input. Whether a
 * value is already held by another record in the vault is a question about the
 * vault rather than about the input, and it is answered further in.
 */
class SaveFieldValuesRequest extends FormRequest
{
    /**
     * The gate is the route binding: a contact is resolved within the account
     * of whoever is asking, and the controller checks it is in the vault the
     * address names and that the section is one its structure carries.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge([
            'fields' => ['array'],
            'year_is_unknown' => ['array'],
            'month_is_unknown' => ['array'],
            /* What a reminder may be shaped like. The set of moments is judged
               below, by the same rule the actions behind this run, and only for
               a reminder whose box is ticked: an untouched one sends an empty
               set, and an empty set is exactly what the rule refuses.

               Whether the zone is one the product knows and whether there is a
               date to be reminded of are questions about the record rather than
               about an input, so they are answered further in and come back as a
               sentence over the block. */
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

    /**
     * Judge the moments of every reminder somebody actually asked for.
     *
     * It runs the same {@see ReminderMoments} the actions run, so the form and
     * the action behind it cannot have different opinions about what a moment
     * is. Without it a duplicate moment, a missing unit and an amount past what
     * its unit allows all reach the action, which throws, and somebody gets a
     * page that failed instead of a sentence under the block.
     *
     * @return array<int, callable>
     */
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

    /**
     * The label the template gives a field, so an error reads "City is
     * required" rather than naming the number the input is keyed by.
     *
     * A field inside a repeater is named the same way, since the number in the
     * middle of its key is the repetition rather than anything a reader knows
     * about: "the file has to be a pdf" is the whole of what went wrong, and the
     * control it is said under is the one that says which repetition.
     *
     * @return array<string, string>
     */
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

    /**
     * Where a refused save goes back to, which is the block open rather than the
     * page it sits on.
     *
     * Laravel would send it back where it came from, and where it came from is
     * the record read-only: the errors would land on markup that draws no
     * controls to put them under, and somebody would be told nothing at all.
     */
    #[Override]
    protected function getRedirectUrl(): string
    {
        return route('contacts.sections.edit', [
            'vault' => $this->route('vault'),
            'contact' => $this->route('contact'),
            'templateVersionSection' => $this->section(),
        ]);
    }

    /**
     * The fields the section asks directly, which are the ones this save may
     * carry answers for.
     *
     * @return Collection<int, Field>
     */
    public function fieldsInTheSection(): Collection
    {
        return $this->section()
            ->sectionVersion
            ->fields
            ->whereNull('parent_field_id')
            ->values();
    }

    /**
     * The section being saved. It is bound by the route, and the controller has
     * already refused one belonging to another structure than the record's.
     */
    public function section(): TemplateVersionSection
    {
        $section = $this->route('templateVersionSection');

        return $section instanceof TemplateVersionSection
            ? $section
            : throw new RuntimeException('This request has no section.');
    }
}
