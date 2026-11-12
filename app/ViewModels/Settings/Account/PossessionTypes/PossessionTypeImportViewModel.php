<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\PossessionTypes;

use App\Models\User;

class PossessionTypeImportViewModel
{
    /**
     * @param  array{name: string, colour: string, icon: string|null, groups: list<array<string, mixed>>, fields: list<array<string, mixed>>}|null  $checked
     * @param  list<string>  $problems
     */
    public function __construct(
        private readonly User $user,
        private readonly string $raw = '',
        private readonly ?array $checked = null,
        private readonly array $problems = [],
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function title(): string
    {
        return __('Import a possession type');
    }

    public function intro(): string
    {
        return __('Paste a possession type file below. It arrives as a new type, and nothing already in this account changes.');
    }

    /** @return list<array{label: string, url?: string}> */
    public function breadcrumb(): array
    {
        return [
            ['label' => __('Possession types'), 'url' => route('settings.possessionTypes.index')],
            ['label' => __('Import')],
        ];
    }

    public function raw(): string
    {
        return $this->raw;
    }

    public function checkUrl(): string
    {
        return route('settings.possessionTypeImportCheck.create');
    }

    public function importUrl(): string
    {
        return route('settings.possessionTypeImport.create');
    }

    /** @return list<string> */
    public function problems(): array
    {
        return $this->problems;
    }

    public function isChecked(): bool
    {
        return $this->checked !== null;
    }

    public function summary(): string
    {
        if ($this->checked === null) {
            return '';
        }

        $fieldCount = count($this->checked['fields']) + array_sum(array_map(
            fn (array $group): int => count($group['fields']),
            $this->checked['groups'],
        ));

        return __('“:name” with :fields and :groups.', [
            'name' => $this->checked['name'],
            'fields' => trans_choice(':count field|:count fields', $fieldCount),
            'groups' => trans_choice(':count group|:count groups', count($this->checked['groups'])),
        ]);
    }
}
