<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
    use HasFactory;

    protected $table = 'journals';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vault_id',
        'name',
        'description',
    ];

    /**
     * Get the vault associated with the journal.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the posts associated with the journal.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get the slices of life associated with the journal.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\SliceOfLife, $this>
     */
    public function slicesOfLife(): HasMany
    {
        return $this->hasMany(SliceOfLife::class);
    }

    /**
     * Get the journal metrics associated with the journal.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\JournalMetric, $this>
     */
    public function journalMetrics(): HasMany
    {
        return $this->hasMany(JournalMetric::class);
    }
}
