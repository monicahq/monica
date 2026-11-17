<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ChangelogVoteDirectionEnum;
use App\Models\ChangelogVote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/** @extends Factory<ChangelogVote> */
class ChangelogVoteFactory extends Factory
{
    protected $model = ChangelogVote::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'entry_id' => (string) Str::ulid(),
            'direction' => ChangelogVoteDirectionEnum::Up,
        ];
    }

    public function down(): static
    {
        return $this->state(['direction' => ChangelogVoteDirectionEnum::Down]);
    }
}
