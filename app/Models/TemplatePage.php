<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TemplatePage extends Model
{
    use HasFactory;

    protected $table = 'template_pages';

    /**
     * Possible template page types.
     */
    public const TYPE_CONTACT = 'contact_information';

    public const TYPE_FEED = 'feed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'template_id',
        'name',
        'position',
        'slug',
        'can_be_deleted',
        'type',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'position' => 'integer',
        'can_be_deleted' => 'boolean',
    ];

    /**
     * Get the account associated with the template page.
     *
     * @return BelongsTo
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * Get the modules associated with the template page.
     *
     * @return BelongsToMany
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'module_template_page')->withTimestamps();
    }
}
