<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TemplatePage extends Model
{
    use HasFactory;

    protected $table = 'template_pages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'template_id',
        'name',
        'position',
    ];

    /**
     * Get the account associated with the template page.
     *
     * @return BelongsTo
     */
    public function template()
    {
        return $this->belongsTo(Template::class);
    }
}
