<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostMetric extends Model
{
    use HasFactory;

    protected $table = 'post_metrics';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'post_id',
        'journal_metric_id',
        'value',
        'label',
    ];

    /**
     * Get the journal metric associated with the post metric.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\JournalMetric, $this>
     */
    public function journalMetric(): BelongsTo
    {
        return $this->belongsTo(JournalMetric::class);
    }

    /**
     * Get the post associated with the post metric.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
