<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Helpers\FieldValueFormatter;
use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\User;
use App\Models\Vault;

/**
 * What a record still holds for questions its structure has stopped asking.
 *
 * Removing a field from a structure does not delete what people wrote in it.
 * The values are set aside: taken off the page, kept, and explained here. That
 * is the whole of the promise, and without this screen only two thirds of it
 * were true, since a value nobody can see is not meaningfully kept.
 *
 * Nothing on this screen writes. Putting a field back is what brings its values
 * back, and that is done on the template rather than on one person: the field
 * belongs to the structure and so does the decision.
 */
class SetAsideValuesViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
    ) {}

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
        return __('Set aside');
    }

    public function contactUrl(): string
    {
        return route('contacts.show', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function templateUrl(): string
    {
        return route('settings.templateBuilder.index', $this->contact->templateVersion->template);
    }

    /**
     * The sentence at the top, which has to explain a state nobody asked for.
     */
    public function intro(): string
    {
        return __('These answers were written down for questions :name is no longer asked. Nothing has been deleted. Put the field back on the template and its answers come back with it.', [
            'name' => $this->contact->first_name ?? $this->contact->name,
        ]);
    }

    public function emptyLine(): string
    {
        return __('Nothing has been set aside. Everything written about :name is on their pages.', [
            'name' => $this->contact->first_name ?? $this->contact->name,
        ]);
    }

    /**
     * Every value the structure stopped asking for, newest first, each with the
     * field it was written in and the day it was taken off the page.
     *
     * The label is the one the field carries now. The key is shown beside it,
     * because a field renamed since is best recognised by the name that never
     * changes.
     *
     * @return array<int, array{label: string, key: string, value: string|null, when: string}>
     */
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
