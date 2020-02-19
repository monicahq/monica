<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface LabelProvider
{
    /**
     * Get the label associated with the contact.
     *
     * @return BelongsToMany
     */
    public function labels();
}
