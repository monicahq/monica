<?php

namespace App\Models;

use App\Enums\ImportJobStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportJob extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'user_id',
        'vault_id',
        'filename',
        'file_path',
        'file_hash',
        'total_rows',
        'processed_rows',
        'failed_rows',
        'status',
        'started_at',
        'completed_at',
        'cancelled_at',
        'last_heartbeat_at',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'progress_pct',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => ImportJobStatus::class,
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'failed_rows' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'last_heartbeat_at' => 'datetime',
    ];

    /**
     * Get the account associated with the import job.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user associated with the import job.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the vault associated with the import job.
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the errors associated with the import job.
     */
    public function errors(): HasMany
    {
        return $this->hasMany(ImportError::class);
    }

    /**
     * Get progress percentage.
     */
    public function getProgressPctAttribute(): float
    {
        if ($this->total_rows <= 0) {
            return 0.0;
        }
        return (float) round((($this->processed_rows + $this->failed_rows) / $this->total_rows) * 100, 2);
    }
}
