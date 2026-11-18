<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AgentFeedbackNote;
use App\Models\AgentFeedbackReport;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<AgentFeedbackNote> */
class AgentFeedbackNoteFactory extends Factory
{
    protected $model = AgentFeedbackNote::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'agent_feedback_report_id' => AgentFeedbackReport::factory(),
            'author_user_id' => null,
            'author_name' => 'Dwight Schrute',
            'body' => fake()->sentence(),
        ];
    }
}
