<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContactListColumnSourceEnum;
use App\Models\ContactListColumn;
use App\Models\Section;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<ContactListColumn> */
class ContactListColumnFactory extends Factory
{
    protected $model = ContactListColumn::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'vault_id' => Vault::factory(),
            'account_id' => fn (array $attributes): int => Vault::query()->findOrFail($attributes['vault_id'])->account_id,
            'source' => ContactListColumnSourceEnum::Email,
            'section_id' => null,
            'field_key' => null,
            'heading' => null,
            'position' => 1,
            'is_visible' => true,
        ];
    }

    public function field(?Section $section = null, string $key = 'company'): static
    {
        return $this->state(fn (array $attributes): array => [
            'source' => ContactListColumnSourceEnum::Field,
            'section_id' => $section->id ?? Section::factory(),
            'field_key' => $key,
        ]);
    }
}
