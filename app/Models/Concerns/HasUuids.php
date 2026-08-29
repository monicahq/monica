<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUuids as EloquentHasUuids;
use Override;

/**
 * A uuid of its own for a record the outside world is allowed to name.
 *
 * The primary key stays a small incrementing integer, which is what foreign
 * keys and indexes are cheapest on, and the uuid is what URLs, share links and
 * the API are shown. Nothing sequential leaves the application that way, so one
 * address cannot be guessed from the one beside it, and the number of records
 * an instance holds is never given away by a link.
 *
 * Wrapping Laravel's trait is what makes that hold. Left alone it fills the
 * primary key, so every model had to say otherwise, in ten identical methods. A
 * model that forgot was not refused: it was handed a uuid for its `id`, which
 * the integer column rejected on the first insert. Saying it once here is what
 * makes forgetting impossible.
 */
trait HasUuids
{
    use EloquentHasUuids;

    /**
     * Get the columns that are given a unique identifier of their own.
     *
     * @return list<string>
     */
    #[Override]
    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}
