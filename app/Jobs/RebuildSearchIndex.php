<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Contact;
use App\Models\Entity;
use App\Models\LifeEvent;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Laravel\Scout\Contracts\UpdatesIndexSettings;
use Laravel\Scout\EngineManager;

/**
 * Empty the search index of one kind of record, then send every record of that kind again. Its
 * settings are written first, as monica:rebuild-search-index does, so an index the engine creates
 * on the way can still be filtered by account and vault.
 *
 * It is on a queue because it walks every record of the instance, and it is unique per kind so that
 * two rebuilds of one index never interleave an emptying and a refill.
 */
class RebuildSearchIndex implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /** How long the lock on an index may outlive a worker that died holding it. */
    public int $uniqueFor = 3600;

    /** @param class-string<Contact|Entity|LifeEvent> $model */
    public function __construct(public readonly string $model) {}

    public function uniqueId(): string
    {
        return $this->model;
    }

    public function handle(EngineManager $engines): void
    {
        if ($engines->engine() instanceof UpdatesIndexSettings) {
            Artisan::call('scout:sync-index-settings');
        }

        $this->model::removeAllFromSearch();
        $this->model::sendToSearch($this->model::query());
    }
}
