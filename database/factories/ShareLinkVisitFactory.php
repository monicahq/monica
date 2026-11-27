<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ShareLink;
use App\Models\ShareLinkVisit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<ShareLinkVisit> */
class ShareLinkVisitFactory extends Factory
{
    protected $model = ShareLinkVisit::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'share_link_id' => ShareLink::factory(),
            'account_id' => fn (array $attributes): int => ShareLink::query()->findOrFail($attributes['share_link_id'])->account_id,
            'ip_address' => fake()->ipv4(),
            'user_agent' => 'Mozilla/5.0',
            'country' => 'US',
            'password_was_required' => false,
            'password_failed' => false,
            'viewed_at' => now(),
        ];
    }

    public function passwordFailed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'password_was_required' => true,
            'password_failed' => true,
        ]);
    }
}
