<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModuleRow extends Model
{
    use HasFactory;

    protected $table = 'module_rows';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'module_id',
        'position',
    ];

    /**
     * Get the module associated with the module.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Get the module row fields associated with the module.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ModuleRowField, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(ModuleRowField::class);
    }
}
