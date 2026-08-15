<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\ShareLinkVisitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ShareLinkVisit
 *
 * One opening of a private link. Every link is journalled with its creation,
 * its last consultation and its revocation, and the first and the last of those
 * are columns on the link itself. This is the middle one, kept in detail.
 *
 * "Last viewed" answers almost nothing on its own. Somebody who shares a page
 * wants to know whether it was opened once or forty times, and roughly when,
 * and that is the question these rows answer.
 *
 * The visitor is never identified. There is no account behind them and none is
 * created, so what is kept is coarse: roughly when, roughly from where, and
 * whether a password had to be given. A failed attempt is written down too,
 * since it is the only signal the owner has that a link may have leaked.
 *
 * @property int $id
 * @property int $account_id
 * @property int $share_link_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $country
 * @property bool $password_was_required
 * @property bool $password_failed
 * @property Carbon $viewed_at
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property-read Account $account
 * @property-read ShareLink $shareLink
 */
class ShareLinkVisit extends Model
{
    /** @use HasFactory<ShareLinkVisitFactory> */
    use HasFactory;

    /**
     * How much of the browser announcement is kept. Anything longer than this
     * is somebody being creative rather than a browser, and the column would
     * refuse it.
     */
    public const int USER_AGENT_LENGTH = 512;

    protected $table = 'share_link_visits';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'share_link_id',
        'ip_address',
        'user_agent',
        'country',
        'password_was_required',
        'password_failed',
        'viewed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password_was_required' => 'boolean',
            'password_failed' => 'boolean',
            'viewed_at' => 'datetime',
        ];
    }

    /**
     * Get the account the link belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the link that was opened.
     *
     * @return BelongsTo<ShareLink, $this>
     */
    public function shareLink(): BelongsTo
    {
        return $this->belongsTo(ShareLink::class);
    }
}
