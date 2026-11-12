<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SupportCategoryEnum;
use App\Enums\SupportRequestStatusEnum;
use App\Models\Account;
use App\Models\SupportRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<SupportRequest> */
class SupportRequestFactory extends Factory
{
    protected $model = SupportRequest::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'number' => fake()->unique()->numberBetween(100000, 999999),
            'account_id' => Account::factory(),
            'user_id' => null,
            'category' => SupportCategoryEnum::Question,
            'subject' => fake()->sentence(4),
            'status' => SupportRequestStatusEnum::ToHandle,
            'application_version' => '5.0.0',
            'locale' => 'en',
            'last_activity_at' => now(),
            'solved_at' => null,
        ];
    }

    public function toHandle(): static
    {
        return $this->state(['status' => SupportRequestStatusEnum::ToHandle, 'solved_at' => null]);
    }

    public function waitingOnUser(): static
    {
        return $this->state(['status' => SupportRequestStatusEnum::WaitingOnUser, 'solved_at' => null]);
    }

    public function solved(): static
    {
        return $this->state(['status' => SupportRequestStatusEnum::Solved, 'solved_at' => now()]);
    }
}
