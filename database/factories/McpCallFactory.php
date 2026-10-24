<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\McpCallResultEnum;
use App\Enums\McpConnectionTypeEnum;
use App\Enums\McpOperationEnum;
use App\Models\Account;
use App\Models\McpCall;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/** @extends Factory<McpCall> */
class McpCallFactory extends Factory
{
    protected $model = McpCall::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'user_id' => fn (array $attributes): int => User::factory()->create(['account_id' => $attributes['account_id']])->id,
            'connection_type' => McpConnectionTypeEnum::ApiKey,
            'connection_id' => '1',
            'connection_name' => 'Jim’s assistant',
            'tool' => 'get-contact',
            'operation' => McpOperationEnum::Read,
            'vault_id' => null,
            'vault_name' => 'Dunder Mifflin',
            'subject_type' => null,
            'subject_id' => null,
            'subject_label' => 'Dwight Schrute',
            'result' => McpCallResultEnum::Succeeded,
            'error_code' => null,
            'error_message' => null,
            'parameters' => '{"vault":"dunder-mifflin","contact":"dwight-schrute"}',
            'parameters_truncated' => false,
            'response' => '{"name":"Dwight Schrute"}',
            'response_truncated' => false,
            'json_rpc_id' => (string) Str::uuid(),
            'ip_address' => '203.0.113.7',
            'started_at' => now(),
            'duration_in_milliseconds' => 58,
        ];
    }

    public function refused(): static
    {
        return $this->state(fn (array $attributes): array => [
            'result' => McpCallResultEnum::Refused,
            'error_code' => -32602,
            'error_message' => 'That contact does not exist.',
            'vault_name' => null,
            'subject_label' => null,
            'response' => null,
        ]);
    }

    public function invalidParameters(): static
    {
        return $this->state(fn (array $attributes): array => [
            'tool' => 'update-life-event',
            'operation' => McpOperationEnum::Update,
            'result' => McpCallResultEnum::InvalidParameters,
            'error_code' => -32602,
            'error_message' => 'The happened on field must match the format Y-m-d.',
            'response' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'result' => McpCallResultEnum::Error,
            'error_code' => -32603,
            'error_message' => 'An internal server error occurred.',
            'response' => null,
        ]);
    }

    public function interrupted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'result' => McpCallResultEnum::Interrupted,
            'response' => null,
            'duration_in_milliseconds' => null,
        ]);
    }

    public function overOAuth(): static
    {
        return $this->state(fn (array $attributes): array => [
            'connection_type' => McpConnectionTypeEnum::OauthClient,
            'connection_id' => (string) Str::uuid(),
            'connection_name' => 'Claude',
        ]);
    }
}
