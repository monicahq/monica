<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Helpers\FieldValueFormatter;
use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\User;
use App\Models\Vault;

class SetAsideValuesViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function contact(): Contact
    {
        return $this->contact;
    }

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function title(): string
    {
        return __('Unused answers');
    }

    public function contactUrl(): string
    {
        return route('contacts.show', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function templateUrl(): string
    {
        return route('settings.templateBuilder.index', $this->contact->templateVersion->template);
    }

    public function intro(): string
    {
        return __('These answers were written down for questions :name is no longer asked. Nothing has been deleted. Put the field back on the template and its answers come back with it.', [
            'name' => $this->contact->first_name ?? $this->contact->name,
        ]);
    }

    public function emptyLine(): string
    {
        return __('There are no unused answers. Everything written about :name is on their pages.', [
            'name' => $this->contact->first_name ?? $this->contact->name,
        ]);
    }

    /** @return array<int, array{label: string, key: string, value: string|null, when: string}> */
    public function values(): array
    {
        $formatter = new FieldValueFormatter($this->user);

        return $this->contact
            ->fieldValues()
            ->where('is_detached', true)
            ->with(['field', 'valueListItem'])
            ->orderByDesc('detached_at')
            ->get()
            ->map(fn (FieldValue $value): array => [
                'label' => $value->field->label,
                'key' => $value->field_key,
                'value' => $formatter->format($value),
                'when' => $value->detached_at?->isoFormat('LL') ?? __('at some point'),
            ])
            ->all();
    }
}
