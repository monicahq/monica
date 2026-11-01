<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DatePrecisionEnum;
use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<GroupMember> */
class GroupMemberFactory extends Factory
{
    protected $model = GroupMember::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'account_id' => fn (array $attributes): int => Group::query()->findOrFail($attributes['group_id'])->account_id,
            'contact_id' => fn (array $attributes): int => Contact::factory()->create([
                'account_id' => $attributes['account_id'],
                'vault_id' => Group::query()->findOrFail($attributes['group_id'])->vault_id,
            ])->id,
            'role' => null,
            'joined_on' => null,
            'joined_precision' => DatePrecisionEnum::Unknown,
            'left_on' => null,
            'left_precision' => DatePrecisionEnum::Unknown,
        ];
    }

    public function former(): static
    {
        return $this->state([
            'left_on' => '2007-01-01',
            'left_precision' => DatePrecisionEnum::Year,
        ]);
    }
}
