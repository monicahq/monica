<?php

namespace App\Models\Account;

use App\Traits\HasUuid;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use App\Notifications\ExportAccountDone;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExportJob extends Model
{
    use HasUuid, HasFactory;

    public const EXPORT_TODO = 'todo';
    public const EXPORT_DOING = 'doing';
    public const EXPORT_DONE = 'done';
    public const EXPORT_FAILED = 'failed';

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
     * @var array<string>
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
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<string>
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

    /**
     * Start the export job.
     *
     * @return void
     */
    public function start(): void
    {
        $this->status = self::EXPORT_DOING;
        $this->started_at = now();
        $this->save();
    }

    /**
     * End the export job.
     *
     * @return void
     */
    public function end(): void
    {
        $this->status = self::EXPORT_DONE;
        $this->ended_at = now();
        $this->save();

        $this->user->notify(new ExportAccountDone($this));
    }
}
