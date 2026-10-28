<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AccountExportStatusEnum;
use App\Models\AccountExport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/** @extends Factory<AccountExport> */
class AccountExportFactory extends Factory
{
    protected $model = AccountExport::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'account_id' => fn (array $attributes): int => User::query()->findOrFail($attributes['user_id'])->account_id,
            'vault_ids' => [],
            'status' => AccountExportStatusEnum::Preparing,
            'disk' => 'local',
            'uuid' => fn (): string => Str::uuid()->toString(),
            'path' => fn (array $attributes): string => "accounts/{$attributes['account_id']}/transfers/{$attributes['uuid']}/monica-export.json",
            'size_in_bytes' => null,
            'ready_at' => null,
            'expires_at' => null,
        ];
    }
}
