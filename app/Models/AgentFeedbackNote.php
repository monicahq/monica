<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use Carbon\CarbonImmutable;
use Database\Factories\AgentFeedbackNoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class AgentFeedbackNote
 *
 * A note an administrator left on an agent feedback report for the other administrators. Nobody else
 * ever reads it. The author's name is copied in, so the note still says who wrote it after that
 * administrator is gone.
 *
 * @property int $id
 * @property string $uuid
 * @property int $agent_feedback_report_id
 * @property int|null $author_user_id
 * @property string $author_name
 * @property string $body
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read AgentFeedbackReport $report
 * @property-read User|null $author
 */
#[Fillable([
    'agent_feedback_report_id',
    'author_user_id',
    'author_name',
    'body',
])]
class AgentFeedbackNote extends Model
{
    /** @use HasFactory<AgentFeedbackNoteFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * Get the report the note is about.
     *
     * @return BelongsTo<AgentFeedbackReport, $this>
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(AgentFeedbackReport::class, 'agent_feedback_report_id');
    }

    /**
     * Get the administrator who wrote it.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }
}
