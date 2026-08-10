<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportJob extends Model
{
    use HasFactory;

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
        'last_processed_row_index',
        'status',
        'failure_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'failed_rows' => 'integer',
        'last_processed_row_index' => 'integer',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    public function errors(): HasMany
    {
        return $this->hasMany(ImportError::class);
    }
}
