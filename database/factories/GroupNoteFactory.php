<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Group;
use App\Models\GroupNote;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<GroupNote> */
class GroupNoteFactory extends Factory
{
    protected $model = GroupNote::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'account_id' => fn (array $attributes): int => Group::query()->findOrFail($attributes['group_id'])->account_id,
            'vault_id' => fn (array $attributes): int => Group::query()->findOrFail($attributes['group_id'])->vault_id,
            'body' => fake()->sentence(),
            'created_by_user_id' => null,
        ];
    }
}
