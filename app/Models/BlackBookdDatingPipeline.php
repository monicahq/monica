<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlackBookdDatingPipeline extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'blackbookd_dating_pipelines';

    public const STAGE_FIRST_CONTACT = 'first_contact_draft_ready';

    public const STAGE_TALKING = 'talking';

    public const STAGE_ASKED_TO_TEXT = 'asked_to_text';

    public const STAGE_MEETING_PLANNED = 'meeting_planned';

    public const STAGE_MET = 'met';

    public const STAGE_DATING = 'dating';

    public const STAGE_PAUSED = 'paused';

    public const STAGE_ENDED = 'ended_boundary_held';

    public const STAGES = [
        self::STAGE_FIRST_CONTACT,
        self::STAGE_TALKING,
        self::STAGE_ASKED_TO_TEXT,
        self::STAGE_MEETING_PLANNED,
        self::STAGE_MET,
        self::STAGE_DATING,
        self::STAGE_PAUSED,
        self::STAGE_ENDED,
    ];

    public const PHONE_UNDECIDED = 'undecided';

    public const PHONE_YES = 'yes';

    public const PHONE_NO = 'no';

    protected $fillable = [
        'vault_id',
        'contact_id',
        'handle',
        'app_source',
        'age',
        'metro_location',
        'role',
        'looking_for',
        'stage',
        'last_message_from',
        'last_message_summary',
        'last_message_at',
        'what_he_said_matters',
        'next_step',
        'red_flags_notes',
        'phone_numbers_exchanged',
        'boundary_held_do_not_reengage',
        'long_term_fit_score',
        'long_term_fit_reason',
    ];

    protected $casts = [
        'age' => 'integer',
        'last_message_at' => 'datetime',
        'boundary_held_do_not_reengage' => 'boolean',
        'long_term_fit_score' => 'integer',
    ];

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function isActive(): bool
    {
        return ! $this->boundary_held_do_not_reengage
            && ! in_array($this->stage, [self::STAGE_ENDED, self::STAGE_PAUSED], true);
    }
}