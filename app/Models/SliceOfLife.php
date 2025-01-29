<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SliceOfLife extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'journal_id',
        'file_cover_image_id',
        'name',
        'description',
    ];

    /**
     * Get the journal associated with the slice of life.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Journal, $this>
     */
    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    /**
     * Get the posts associated with the slice of life.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get the file associated with the slice of life.
     * If it exists, it's the header image.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\File, $this>
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_cover_image_id');
    }
}
