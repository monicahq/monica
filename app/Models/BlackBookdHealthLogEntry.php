<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlackBookdHealthLogEntry extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'blackbookd_health_log_entries';

    public const TYPE_STI_TEST = 'sti_test';

    public const TYPE_PREP_REFILL = 'prep_refill';

    public const TYPE_DOXYPEP = 'doxypep';

    public const TYPE_VACCINE = 'vaccine';

    protected $fillable = [
        'vault_id',
        'entry_type',
        'occurred_on',
        'payload',
        'notes',
    ];

    protected $casts = [
        'occurred_on' => 'date',
        'payload' => 'array',
    ];

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }
}