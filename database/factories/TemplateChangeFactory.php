<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Template;
use App\Models\TemplateChange;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<TemplateChange> */
class TemplateChangeFactory extends Factory
{
    protected $model = TemplateChange::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'vault_id' => Vault::factory(),
            'account_id' => fn (array $attributes): int => Vault::query()->findOrFail($attributes['vault_id'])->account_id,
            'user_id' => null,
            'entity_type_id' => null,
            'from_template_id' => fn (array $attributes): int => Template::factory()->published()->create(['account_id' => $attributes['account_id']])->id,
            'from_version_ids' => [],
            'structure_version_id' => fn (array $attributes): int => Template::factory()->published()->create(['account_id' => $attributes['account_id']])->structure->published_version_id,
            'items_total' => 0,
            'moved_count' => 0,
            'skipped_count' => 0,
            'failed_count' => 0,
            'ended_at' => null,
        ];
    }
}
