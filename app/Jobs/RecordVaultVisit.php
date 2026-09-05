<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Note that a vault has just been opened, which is what puts it first on the list and marks it as
 * the one you were last in. Opening a vault is a read, so this writes the one column with a plain
 * query rather than through the model, which would bump `updated_at`.
 */
class RecordVaultVisit implements ShouldQueue
{
    use Queueable;

    private CarbonImmutable $visitedAt;

    public function __construct(
        public Vault $vault,
        ?CarbonImmutable $visitedAt = null,
    ) {
        $this->visitedAt = $visitedAt ?? CarbonImmutable::now();
    }

    public function handle(): void
    {
        DB::table('vaults')
            ->where('id', $this->vault->id)
            ->update(['last_visited_at' => $this->visitedAt]);
    }
}
