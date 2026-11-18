<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BlackBookdEncounter extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'blackbookd_encounters';

    public const PROTECTION_OPTIONS = ['condom', 'prep', 'doxypep', 'none'];

    public const APPS = ['grindr', 'sniffies', 'scruff', 'fetlife', 'other'];

    protected $fillable = [
        'vault_id',
        'occurred_at',
        'location_label',
        'how_met_category',
        'how_met_app',
        'how_met_detail',
        'protection_used',
        'activity_tags',
        'substances',
        'rating',
        'private_notes',
        'consent_notes',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'protection_used' => 'array',
        'activity_tags' => 'array',
        'rating' => 'integer',
    ];

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'blackbookd_encounter_contact', 'blackbookd_encounter_id');
    }
}