<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ChangelogSeenEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/** @extends Factory<ChangelogSeenEntry> */
class ChangelogSeenEntryFactory extends Factory
{
    protected $model = ChangelogSeenEntry::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'entry_id' => (string) Str::ulid(),
        ];
    }
}
