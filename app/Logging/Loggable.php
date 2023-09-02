<?php

namespace App\Logging;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property string $id
 * @property ?string $current_logid
 */
interface Loggable
{
    /**
     * Get the associated logs.
     */
    public function logs(): MorphMany;
}
