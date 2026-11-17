<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ChangelogCommentStatusEnum;
use App\Models\ChangelogComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/** @extends Factory<ChangelogComment> */
class ChangelogCommentFactory extends Factory
{
    protected $model = ChangelogComment::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'entry_id' => (string) Str::ulid(),
            'body' => fake()->sentence(),
            'is_private' => false,
            'is_anonymous' => false,
            'status' => ChangelogCommentStatusEnum::New,
            'reviewed_at' => null,
        ];
    }

    public function private(): static
    {
        return $this->state(['is_private' => true, 'is_anonymous' => false]);
    }

    public function anonymous(): static
    {
        return $this->state(['is_private' => false, 'is_anonymous' => true]);
    }

    public function reviewed(): static
    {
        return $this->state(['status' => ChangelogCommentStatusEnum::Reviewed, 'reviewed_at' => now()]);
    }

    public function hidden(): static
    {
        return $this->state(['status' => ChangelogCommentStatusEnum::Hidden, 'reviewed_at' => now()]);
    }
}
