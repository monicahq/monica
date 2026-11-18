<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlackBookdContactProfile extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'blackbookd_contact_profiles';

    public const RELATIONSHIP_TYPES = [
        'partner', 'fwb', 'play_partner', 'date', 'one_time', 'other',
    ];

    protected $fillable = [
        'contact_id',
        'hiv_status',
        'on_prep',
        'last_test_date',
        'kinks',
        'boundaries',
        'relationship_type',
        'notes',
    ];

    protected $casts = [
        'on_prep' => 'boolean',
        'last_test_date' => 'date',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}