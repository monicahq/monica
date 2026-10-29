<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportError extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'import_job_id',
        'row_number',
        'row_data',
        'error',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'row_number' => 'integer',
        'row_data' => 'array',
    ];

    /**
     * Get the import job that owns this error.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\ImportJob, $this>
     */
    public function importJob(): BelongsTo
    {
        return $this->belongsTo(ImportJob::class, 'import_job_id');
    }
}
