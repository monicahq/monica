<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Helpers\Duration;
use App\Helpers\FieldValueRules;
use App\Models\Contact;
use App\Models\Field;
use App\Models\LifeEvent;
use App\Models\LifeEventType;
use App\Models\StructureVersion;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Override;

class SaveLifeEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        $rules = [
            'life_event_type_id' => ['required', 'integer'],
            'happened_on' => ['required', 'date'],
            'participants' => ['nullable', 'array'],
            'participants.*' => ['integer', 'distinct'],
            'title' => ['nullable', 'string', 'max:255'],
            'narrative' => ['nullable', 'string', 'max:65535'],
            'location' => ['nullable', 'string', 'max:255'],
            'duration_in_minutes' => ['nullable', 'integer', 'min:1', 'max:'.Duration::LONGEST_IN_MINUTES],
        ];

        if (! $this->route('lifeEvent') instanceof LifeEvent) {
            return $rules;
        }

        return [
            ...$rules,
            'kept_files' => ['nullable', 'array'],
            'kept_files.*' => ['integer'],
        ];
    }

    public function lifeEventType(): LifeEventType
    {
        return LifeEventType::query()
            ->where('account_id', $this->user()->account_id)
            ->findOrFail($this->validated('life_event_type_id'));
    }

    /** @return array<int, mixed> */
    public function values(?StructureVersion $version): array
    {
        $fields = $version?->inputFields() ?? new Collection;

        try {
            return $this->validate(
                FieldValueRules::for($fields, $this->vault()),
                [],
                $fields->mapWithKeys(fn (Field $field): array => ["fields.{$field->id}" => mb_strtolower($field->label)])->all(),
            )['fields'] ?? [];
        } catch (ValidationException $e) {
            throw $e->redirectTo($this->getRedirectUrl());
        }
    }

    /** @return Collection<int, Contact> */
    public function participants(Contact $contact): Collection
    {
        $ids = array_values(array_diff($this->validated('participants') ?? [], [$contact->id]));

        return Contact::query()
            ->where('vault_id', $this->vault()->id)
            ->findOrFail($ids)
            ->sortBy(fn (Contact $participant): int|false => array_search($participant->id, $ids, true))
            ->values();
    }

    #[Override]
    protected function getRedirectUrl(): string
    {
        $lifeEvent = $this->route('lifeEvent');

        if (! $lifeEvent instanceof LifeEvent) {
            return parent::getRedirectUrl();
        }

        return route('contacts.lifeEvents.edit', [
            'vault' => $this->route('vault'),
            'contact' => $this->route('contact'),
            'lifeEvent' => $lifeEvent,
        ]);
    }

    private function vault(): Vault
    {
        /** @var Vault */
        return $this->route('vault');
    }
}
