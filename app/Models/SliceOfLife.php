<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SliceOfLife extends Model
{
    use HasFactory;

    protected $table = 'slices_of_life';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'journal_id',
        'file_cover_image_id',
        'name',
        'description',
    ];

    /**
     * Get the journal associated with the slice of life.
     */
    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    /**
     * Get the posts associated with the slice of life.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get the file associated with the slice of life.
     * If it exists, it's the header image.
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_cover_image_id');
    }
}
