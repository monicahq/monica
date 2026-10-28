<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AccountImportStatusEnum;
use App\Models\AccountImport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/** @extends Factory<AccountImport> */
class AccountImportFactory extends Factory
{
    protected $model = AccountImport::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'account_id' => fn (array $attributes): int => User::query()->findOrFail($attributes['user_id'])->account_id,
            'source_format' => null,
            'source_checksum' => fake()->sha256(),
            'disk' => 'local',
            'uuid' => fn (): string => Str::uuid()->toString(),
            'path' => fn (array $attributes): string => "accounts/{$attributes['account_id']}/transfers/{$attributes['uuid']}/upload.json",
            'status' => AccountImportStatusEnum::Reading,
            'refusal' => null,
            'vault_id' => null,
            'created_vault_ids' => [],
            'me_source_uuid' => null,
            'summary' => null,
            'items_total' => 0,
            'items_done' => 0,
            'ended_at' => null,
        ];
    }
}
