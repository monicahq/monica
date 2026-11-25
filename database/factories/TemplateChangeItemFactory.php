<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\TemplateChange;
use App\Models\TemplateChangeItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<TemplateChangeItem> */
class TemplateChangeItemFactory extends Factory
{
    protected $model = TemplateChangeItem::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'template_change_id' => TemplateChange::factory(),
            'account_id' => fn (array $attributes): int => TemplateChange::query()->findOrFail($attributes['template_change_id'])->account_id,
            'record_type' => Contact::class,
            'record_id' => fn (array $attributes): int => Contact::factory()->create(['account_id' => $attributes['account_id']])->id,
            'from_version_id' => fn (array $attributes): int => Contact::query()->findOrFail($attributes['record_id'])->structure_version_id,
            'outcome' => null,
            'reason' => null,
        ];
    }
}
