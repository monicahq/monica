<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A record that goes to the vault's trash with the person or the group it belongs to.
 *
 * The record remembers what it was deleted with, so restoring that owner brings it back and
 * deleting the owner for good takes it too. A record deleted on its own remembers nothing, and
 * stays in the trash when its owner comes back.
 *
 * @property string|null $deleted_with_type
 * @property int|null $deleted_with_id
 * @property-read Model|null $deletedWith
 */
trait GoesToTheTrashWithItsOwner
{
    /**
     * Get the record this one was deleted with.
     *
     * @return MorphTo<Model, $this>
     */
    public function deletedWith(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'deleted_with_type', 'deleted_with_id');
    }

    /**
     * Send the record to the trash with its owner.
     */
    public function deleteWith(Model $owner): void
    {
        $this->forceFill([
            'deleted_with_type' => $owner->getMorphClass(),
            'deleted_with_id' => $owner->getKey(),
        ])->save();

        $this->delete();
    }

    /**
     * Bring the record back, forgetting what it was deleted with.
     */
    public function restoreFromTheTrash(): void
    {
        $this->forceFill([
            'deleted_with_type' => null,
            'deleted_with_id' => null,
        ]);

        $this->restore();
    }

    /**
     * Narrow to the trashed records deleted with an owner.
     *
     * @param  Builder<static>  $query
     */
    public function scopeDeletedWithOwner(Builder $query, Model $owner): void
    {
        $query->onlyTrashed()
            ->where('deleted_with_type', $owner->getMorphClass())
            ->where('deleted_with_id', $owner->getKey());
    }
}
