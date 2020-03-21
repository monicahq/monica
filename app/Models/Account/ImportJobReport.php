<?php

namespace App\Models\Account;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Account $account
 * @property int $account_id
 * @property User $user
 * @property int $user_id
 * @property int $import_job_id
 * @property string $contact_information
 * @property bool $skipped
 * @property string $skip_reason
 */
class ImportJobReport extends Model
{
    protected $table = 'import_job_reports';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the account record associated with the import job report.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user record associated with the import job report.
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the import job record associated with the gift.
     *
     * @return BelongsTo
     */
    public function importJob()
    {
        return $this->belongsTo(ImportJob::class);
    }
}
