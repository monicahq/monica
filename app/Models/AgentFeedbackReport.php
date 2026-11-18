<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AgentFeedbackKindEnum;
use App\Enums\AgentFeedbackSeverityEnum;
use App\Enums\AgentFeedbackStatusEnum;
use App\Models\Concerns\HasUuids;
use Carbon\CarbonImmutable;
use Database\Factories\AgentFeedbackReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * Class AgentFeedbackReport
 *
 * A problem an AI agent met with the MCP server while working in somebody's account, sent through the
 * submit-feedback tool: what failed, what it expected, and the fix it suggests.
 *
 * It goes to the administrators of the instance, never to the person, and goes with the account when
 * the account is deleted. The agent is told to paraphrase what the person asked and to leave names,
 * dates and other details from the vault out, so administrators read about the product and not about
 * the people somebody keeps in it. Nothing checks that it did.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int|null $user_id
 * @property AgentFeedbackStatusEnum $status
 * @property AgentFeedbackKindEnum $kind
 * @property AgentFeedbackSeverityEnum $severity
 * @property string $title
 * @property string|null $tool
 * @property string|null $request
 * @property list<array{tool: string, arguments: string|null, outcome: string, result: string|null}>|null $steps
 * @property string $observed
 * @property string $expected
 * @property string $proposed_fix
 * @property string|null $proposed_code
 * @property string|null $model
 * @property string $connection_name
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read User|null $user
 * @property-read Collection<int, AgentFeedbackNote> $notes
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'user_id',
    'status',
    'kind',
    'severity',
    'title',
    'tool',
    'request',
    'steps',
    'observed',
    'expected',
    'proposed_fix',
    'proposed_code',
    'model',
    'connection_name',
])]
class AgentFeedbackReport extends Model
{
    /** @use HasFactory<AgentFeedbackReportFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * Get the account the agent was working in.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user the agent was working for.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the notes administrators left on it, oldest first.
     *
     * @return HasMany<AgentFeedbackNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(AgentFeedbackNote::class)->orderBy('created_at')->orderBy('id');
    }

    /** @return array<string, string> */
    #[Override]
    protected function casts(): array
    {
        return [
            'status' => AgentFeedbackStatusEnum::class,
            'kind' => AgentFeedbackKindEnum::class,
            'severity' => AgentFeedbackSeverityEnum::class,
            'steps' => 'array',
        ];
    }
}
