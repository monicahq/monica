<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Contact;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Note that a contact's page has just been opened, which is what lists them among the people
 * recently viewed on the vault's dashboard. Opening a page is a read, so this writes the one column
 * with a plain query rather than through the model, which would bump `updated_at`.
 */
class RecordContactVisit implements ShouldQueue
{
    use Queueable;

    private CarbonImmutable $visitedAt;

    public function __construct(
        public Contact $contact,
        ?CarbonImmutable $visitedAt = null,
    ) {
        $this->visitedAt = $visitedAt ?? CarbonImmutable::now();
    }

    public function handle(): void
    {
        DB::table('contacts')
            ->where('id', $this->contact->id)
            ->update(['last_consulted_at' => $this->visitedAt]);
    }
}
