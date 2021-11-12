<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    /**
     * Get model's Uuid.
     *
     * @return string
     */
    public function getUuidAttribute()
    {
        if (! isset($this->attributes['uuid']) || empty($this->attributes['uuid']) || $this->attributes['uuid'] == null) {
            return tap(Str::uuid()->toString(), function ($uuid) {
                $this->forceFill([
                    'uuid' => $uuid,
                ]);
                $this->save(['timestamps' => false]);
            });
        }

        return $this->attributes['uuid'];
    }
}
