<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\McpCallResultEnum;
use App\Enums\McpConnectionTypeEnum;
use App\Enums\McpOperationEnum;
use App\Models\Concerns\HasUuids;
use Carbon\CarbonImmutable;
use Database\Factories\McpCallFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * One call an assistant made to a tool of the MCP server.
 *
 * It is a record of access, kept apart from the changelog on purpose. The
 * changelog says what changed; this says what was asked, including the reads,
 * the refusals and the failures, which the changelog never sees.
 *
 * The row is written before the tool runs, with the result `interrupted`, and
 * closed once with the real result when the tool returns. A call that never
 * returned therefore stays interrupted, and can never pass for a success. The
 * result is read from what the tool raised or answered, never from the HTTP
 * status, which is 200 for a failed tool call as much as for a good one.
 *
 * The connection, the vault and the record are copied by name when the call is
 * made, so the history still reads after a key is revoked, an assistant is
 * disconnected or a contact is deleted. The vault and the record are only ever
 * the ones found inside the account: a call aimed at somebody else's data names
 * nothing of it.
 *
 * Parameters and response are masked before they are written and capped in
 * size, so the table never holds a secret, a password or the value of a
 * sensitive field.
 *
 * Nothing edits or deletes a row except age: past
 * `monica.mcp_call_retention_in_days` it is pruned by the daily `model:prune`.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $user_id
 * @property McpConnectionTypeEnum $connection_type
 * @property string $connection_id
 * @property string $connection_name
 * @property string $tool
 * @property McpOperationEnum|null $operation
 * @property int|null $vault_id
 * @property string|null $vault_name
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string|null $subject_label
 * @property McpCallResultEnum $result
 * @property int|null $error_code
 * @property string|null $error_message
 * @property string|null $parameters
 * @property bool $parameters_truncated
 * @property string|null $response
 * @property bool $response_truncated
 * @property string|null $json_rpc_id
 * @property string|null $ip_address
 * @property CarbonImmutable $started_at
 * @property int|null $duration_in_milliseconds
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read User $user
 * @property-read Vault|null $vault
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'user_id',
    'connection_type',
    'connection_id',
    'connection_name',
    'tool',
    'operation',
    'vault_id',
    'vault_name',
    'subject_type',
    'subject_id',
    'subject_label',
    'result',
    'error_code',
    'error_message',
    'parameters',
    'parameters_truncated',
    'response',
    'response_truncated',
    'json_rpc_id',
    'ip_address',
    'started_at',
    'duration_in_milliseconds',
])]
class McpCall extends Model
{
    /**
     * The periods the history can be narrowed to, in days back from now. All
     * is the whole retention, so it sets no bound.
     *
     * @var array<string, int|null>
     */
    public const array PERIODS = [
        'day' => 1,
        'week' => 7,
        'month' => 30,
        'all' => null,
    ];

    /** @use HasFactory<McpCallFactory> */
    use HasFactory;

    use HasUuids;
    use MassPrunable;

    /**
     * Two calls an assistant makes in the same second are told apart and kept
     * in order by their milliseconds, which the default format would drop.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s.v';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'connection_type' => McpConnectionTypeEnum::class,
            'operation' => McpOperationEnum::class,
            'result' => McpCallResultEnum::class,
            'subject_id' => 'integer',
            'error_code' => 'integer',
            'parameters_truncated' => 'boolean',
            'response_truncated' => 'boolean',
            'started_at' => 'immutable_datetime',
            'duration_in_milliseconds' => 'integer',
        ];
    }

    /**
     * Get the account the call was made on.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user whose connection made the call.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the vault the call reached, while it still exists.
     *
     * @return BelongsTo<Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Only the calls matching what somebody narrowed the history to, each
     * filter left out when it is null. A period counts back from now in days,
     * and a connection is named as its type and its id joined by a colon, the
     * way the filters write it.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeMatching(Builder $query, ?string $period, ?string $connection, ?string $result, ?string $tool): void
    {
        $days = self::PERIODS[$period ?? 'all'] ?? null;

        if ($days !== null) {
            $query->where('started_at', '>=', now()->subDays($days));
        }

        if ($connection !== null) {
            [$type, $id] = explode(':', $connection, 2) + [1 => ''];

            $query->where('connection_type', $type)->where('connection_id', $id);
        }

        if ($result !== null) {
            $query->where('result', $result);
        }

        if ($tool !== null) {
            $query->where('tool', $tool);
        }
    }

    /**
     * The calls old enough to be removed under the retention policy.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where(
            'started_at',
            '<',
            now()->subDays(max(1, (int) config('monica.mcp_call_retention_in_days'))),
        );
    }
}
