<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids as BaseHasUuids;

trait HasUuids
{
    use BaseHasUuids;

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array
     */
    #[\Override]
    public function uniqueIds()
    {
        return ['uuid'];
    }

    /**
     * Get the uuid of the group.
     *
     * @return Attribute<string,?string>
     */
    protected function uuid(): Attribute
    {
        return Attribute::make(
            get: function (?string $value, array $attributes) {
                if (! isset($attributes['uuid'])) {
                    return tap($this->newUniqueId(), function ($uuid) {
                        $this->forceFill(['uuid' => $uuid]);
                    });
                }

                return $attributes['uuid'];
            },
            set: fn (string $value) => $this->isValidUniqueId($value) ? $value : null,
        )->shouldCache();
    }
}
