<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Helpers\FieldValueRules;
use App\Models\Contact;
use App\Models\Field;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * What one page of a record may be saved with.
 *
 * The rules cannot be written down here, because the questions are whatever
 * somebody invented in the template builder. They are read off the fields the
 * page carries, by {@see FieldValueRules}, so the interface and the API enforce
 * the same thing by running the same code.
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
     * address names.
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
        ], FieldValueRules::for($this->fieldsOnThePage()));
    }

    /**
     * The label the template gives a field, so an error reads "City is
     * required" rather than naming the number the input is keyed by.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->fieldsOnThePage()
            ->mapWithKeys(fn (Field $field): array => ["fields.{$field->id}" => mb_strtolower($field->label)])
            ->all();
    }

    /**
     * The fields the page asks directly, which are the ones this save may carry
     * answers for.
     *
     * @return Collection<int, Field>
     */
    public function fieldsOnThePage(): Collection
    {
        $page = $this->page();

        if ($page === null) {
            return collect();
        }

        return $this->contact()->templateVersion->assembly
            ->where('template_version_page_id', $page->id)
            ->flatMap(fn (TemplateVersionSection $row): Collection => $row->sectionVersion->fields)
            ->whereNull('parent_field_id')
            ->values();
    }

    /**
     * The page being saved, which is null when the address names none or names
     * one this record's structure does not have.
     */
    public function page(): ?TemplateVersionPage
    {
        $key = $this->route('page');

        if (! is_string($key) || $key === '') {
            return null;
        }

        return $this->contact()->templateVersion->pages->firstWhere('key', $key);
    }

    private function contact(): Contact
    {
        $contact = $this->route('contact');

        return $contact instanceof Contact ? $contact : throw new RuntimeException('This request has no contact.');
    }
}
