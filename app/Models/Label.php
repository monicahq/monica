<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Label extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'vault_id',
        'name',
        'slug',
        'description',
        'bg_color',
        'text_color',
    ];

    /**
     * Get the vault associated with the label.
     *
     * @return BelongsTo
     */
    public function vault()
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the contacts associated with the label.
     *
     * @return BelongsToMany
     */
    public function contacts()
    {
        return $this->belongsToMany(Contact::class);
    }
}
