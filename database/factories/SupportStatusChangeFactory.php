<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SupportRequestStatusEnum;
use App\Models\SupportRequest;
use App\Models\SupportStatusChange;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<SupportStatusChange> */
class SupportStatusChangeFactory extends Factory
{
    protected $model = SupportStatusChange::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'support_request_id' => SupportRequest::factory(),
            'from_status' => null,
            'to_status' => SupportRequestStatusEnum::ToHandle,
            'user_id' => null,
            'user_name' => fake()->name(),
        ];
    }
}
