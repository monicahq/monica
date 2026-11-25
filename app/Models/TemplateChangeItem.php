<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TemplateChangeOutcomeEnum;
use App\Enums\TemplateChangeReasonEnum;
use Carbon\CarbonImmutable;
use Database\Factories\TemplateChangeItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Override;

/**
 * One record of a change of template, and what became of it.
 *
 * It names the version the record was on when the change was confirmed. A record found on another
 * version when its turn comes was changed after the preview, and is skipped with that reason
 * rather than moved. An item with an outcome is never dealt with again, which is what makes a
 * retried slice and a second copy of the job safe.
 *
 * The record is a morph, so `record_type` and `record_id` carry no foreign key. The item is never
 * addressed by a URL: it is reached through its change.
 *
 * @property int $id
 * @property int $account_id
 * @property int $template_change_id
 * @property string $record_type
 * @property int $record_id
 * @property int $from_version_id
 * @property TemplateChangeOutcomeEnum|null $outcome
 * @property TemplateChangeReasonEnum|null $reason
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read TemplateChange $templateChange
 * @property-read Contact|Entity|null $record
 */
#[Fillable([
    'account_id',
    'template_change_id',
    'record_type',
    'record_id',
    'from_version_id',
    'outcome',
    'reason',
])]
class TemplateChangeItem extends Model
{
    /** @use HasFactory<TemplateChangeItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'outcome' => TemplateChangeOutcomeEnum::class,
            'reason' => TemplateChangeReasonEnum::class,
        ];
    }

    /**
     * Get the account of the change.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the change it belongs to.
     *
     * @return BelongsTo<TemplateChange, $this>
     */
    public function templateChange(): BelongsTo
    {
        return $this->belongsTo(TemplateChange::class);
    }

    /**
     * Get the record to move, archived or deleted ones included, since the reason it was skipped names it.
     *
     * @return MorphTo<Model, $this>
     */
    public function record(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }
}
