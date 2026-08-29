<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Note that a vault has just been opened, which is what puts it first on the list
 * of them and marks it as the one you were last in.
 *
 * It is a job rather than a write in the controller because opening a vault is a
 * read: nobody is waiting to be told their visit was recorded, and the request
 * should not carry a write it does not need. The moment is taken in the
 * constructor, when the vault was opened, rather than in the handler, which runs
 * whenever the queue gets to it.
 *
 * It writes the one column with a plain query rather than through the model. Saving
 * the model would bump `updated_at`, and opening a vault changes nothing about it,
 * so a read must not look like an edit. Nothing is logged either: the logs are for
 * what somebody did, and there would be a line in them for every page of every
 * visit.
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
