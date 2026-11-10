<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\File;
use App\Models\Group;
use App\Models\GroupFile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<GroupFile> */
class GroupFileFactory extends Factory
{
    protected $model = GroupFile::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'account_id' => fn (array $attributes): int => Group::query()->findOrFail($attributes['group_id'])->account_id,
            'file_id' => fn (array $attributes): int => File::factory()->used()->create([
                'account_id' => $attributes['account_id'],
                'vault_id' => Group::query()->findOrFail($attributes['group_id'])->vault_id,
            ])->id,
            'added_by_user_id' => null,
        ];
    }
}
