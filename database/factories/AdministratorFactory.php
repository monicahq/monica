<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Administrator>
 */
class AdministratorFactory extends Factory
{
    protected $model = Administrator::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'granted_by_user_id' => null,
        ];
    }

    /**
     * Indicate that another administrator granted the capacity, rather than the
     * console designating them.
     */
    public function grantedBy(User $administrator): static
    {
        return $this->state(fn (array $attributes): array => [
            'granted_by_user_id' => $administrator->id,
        ]);
    }
}
