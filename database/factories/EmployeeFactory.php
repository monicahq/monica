<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'employee_number' => fake()->unique()->numerify('EMP-####'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'work_email' => fake()->unique()->companyEmail(),
            'country' => fake()->countryCode(),
            'timezone' => 'UTC',
            'hired_at' => fake()->dateTimeBetween('-10 years', '-1 month'),
        ];
    }

    /**
     * Indicate that the employee filled in the information they keep private.
     */
    public function withPrivateInformation(): static
    {
        return $this->state(fn (array $attributes): array => [
            'personal_email' => fake()->unique()->safeEmail(),
            'personal_phone' => fake()->phoneNumber(),
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-20 years'),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->phoneNumber(),
            'emergency_contact_relationship' => 'spouse',
            'home_address' => fake()->address(),
        ]);
    }

    /**
     * Indicate that the employee left the company.
     */
    public function departed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'departed_at' => fake()->dateTimeBetween('-1 month', '-1 day'),
        ]);
    }
}
