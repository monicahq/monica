<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AgentFeedbackKindEnum;
use App\Enums\AgentFeedbackSeverityEnum;
use App\Enums\AgentFeedbackStatusEnum;
use App\Models\Account;
use App\Models\AgentFeedbackReport;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<AgentFeedbackReport> */
class AgentFeedbackReportFactory extends Factory
{
    protected $model = AgentFeedbackReport::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'user_id' => null,
            'status' => AgentFeedbackStatusEnum::New,
            'kind' => AgentFeedbackKindEnum::Bug,
            'severity' => AgentFeedbackSeverityEnum::NeedsWorkaround,
            'title' => fake()->sentence(6),
            'tool' => 'create-contact-reminder',
            'request' => 'Add a yearly reminder for the birthday of a contact.',
            'steps' => [
                ['tool' => 'create-contact-reminder', 'arguments' => '{"contact": "<contact>", "day": 29, "month": 2}', 'outcome' => 'error', 'result' => 'The date field must be a valid date.'],
            ],
            'observed' => 'A reminder on 29 February is refused.',
            'expected' => 'The reminder is created.',
            'proposed_fix' => 'Validate the day against a leap year when no year is given.',
            'proposed_code' => null,
            'model' => 'Claude Opus',
            'connection_name' => 'Claude',
        ];
    }

    public function accepted(): static
    {
        return $this->state(['status' => AgentFeedbackStatusEnum::Accepted]);
    }

    public function fixed(): static
    {
        return $this->state(['status' => AgentFeedbackStatusEnum::Fixed]);
    }

    public function dismissed(): static
    {
        return $this->state(['status' => AgentFeedbackStatusEnum::Dismissed]);
    }
}
