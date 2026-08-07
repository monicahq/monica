<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserActionEnum;
use App\Models\Company;
use App\Models\Log;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Log>
 */
class LogFactory extends Factory
{
    protected $model = Log::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'user_email' => fake()->safeEmail(),
            'action' => UserActionEnum::CompanyUpdate->value,
            'parameters' => null,
        ];
    }
}
