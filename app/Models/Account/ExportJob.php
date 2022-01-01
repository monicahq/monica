<?php

namespace App\Models\Account;

use App\Traits\HasUuid;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportJob extends Model
{
    use HasUuid;

    const EXPORT_TODO = 'todo';
    const EXPORT_DOING = 'doing';
    const EXPORT_DONE = 'done';
    const EXPORT_FAILED = 'faile';

    /**
     * Export as SQL format.
     *
     * @var string
     */
    public const SQL = 'sql';

    /**
     * Export as JSON format.
     *
     * @var string
     */
    public const JSON = 'json';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'account_id',
        'user_id',
        'type',
        'status',
        'filesystem',
        'filename',
        'started_at',
        'ended_at',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'started_at',
        'ended_at',
    ];

    /**
     * Get the account record associated with the import job.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user record associated with the import job.
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
