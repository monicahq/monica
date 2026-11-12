<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SupportMessageKindEnum;
use App\Models\SupportMessage;
use App\Models\SupportRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<SupportMessage> */
class SupportMessageFactory extends Factory
{
    protected $model = SupportMessage::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'support_request_id' => SupportRequest::factory(),
            'author_user_id' => null,
            'author_name' => fake()->name(),
            'kind' => SupportMessageKindEnum::Message,
            'body' => fake()->paragraph(),
        ];
    }

    public function reply(): static
    {
        return $this->state(['kind' => SupportMessageKindEnum::Reply]);
    }

    public function note(): static
    {
        return $this->state(['kind' => SupportMessageKindEnum::Note]);
    }
}
