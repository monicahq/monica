<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Relationship extends Model
{
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
