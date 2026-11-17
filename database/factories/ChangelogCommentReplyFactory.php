<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ChangelogComment;
use App\Models\ChangelogCommentReply;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<ChangelogCommentReply> */
class ChangelogCommentReplyFactory extends Factory
{
    protected $model = ChangelogCommentReply::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'changelog_comment_id' => ChangelogComment::factory(),
            'author_user_id' => null,
            'author_name' => fake()->name(),
            'body' => fake()->sentence(),
            'is_public' => true,
        ];
    }

    public function emailed(): static
    {
        return $this->state(['is_public' => false]);
    }
}
