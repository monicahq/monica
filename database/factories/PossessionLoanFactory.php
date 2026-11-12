<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PossessionLoanDirectionEnum;
use App\Enums\PossessionLoanStatusEnum;
use App\Models\Contact;
use App\Models\Possession;
use App\Models\PossessionLoan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<PossessionLoan> */
class PossessionLoanFactory extends Factory
{
    protected $model = PossessionLoan::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'possession_id' => Possession::factory(),
            'account_id' => fn (array $attributes): int => Possession::query()->findOrFail($attributes['possession_id'])->account_id,
            'vault_id' => fn (array $attributes): int => Possession::query()->findOrFail($attributes['possession_id'])->vault_id,
            'contact_id' => fn (array $attributes): int => Contact::factory()->create([
                'account_id' => $attributes['account_id'],
                'vault_id' => $attributes['vault_id'],
            ])->id,
            'direction' => PossessionLoanDirectionEnum::Lent,
            'status' => PossessionLoanStatusEnum::Active,
            'started_on' => '2024-06-12',
            'due_on' => null,
            'returned_on' => null,
            'is_in_provenance' => false,
        ];
    }

    public function planned(): self
    {
        return $this->state(['status' => PossessionLoanStatusEnum::Planned]);
    }

    public function active(): self
    {
        return $this->state(['status' => PossessionLoanStatusEnum::Active]);
    }

    public function returned(): self
    {
        return $this->state([
            'status' => PossessionLoanStatusEnum::Returned,
            'returned_on' => '2024-06-15',
        ]);
    }
}
