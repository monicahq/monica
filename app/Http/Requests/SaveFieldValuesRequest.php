<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Helpers\FieldValueRules;
use App\Models\Contact;
use App\Models\Field;
use App\Models\TemplateVersionSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
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
        ], FieldValueRules::for($this->fieldsInTheSection()));
    }

    /**
     * The label the template gives a field, so an error reads "City is
     * required" rather than naming the number the input is keyed by.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->fieldsInTheSection()
            ->mapWithKeys(fn (Field $field): array => ["fields.{$field->id}" => mb_strtolower($field->label)])
            ->all();
    }

    /**
     * Where a refused save goes back to, which is the block open rather than the
     * page it sits on.
     *
     * Laravel would send it back where it came from, and where it came from is
     * the record read-only: the errors would land on markup that draws no
     * controls to put them under, and somebody would be told nothing at all.
     */
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
