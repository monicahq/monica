<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleRowField extends Model
{
    use HasFactory;

    protected $table = 'module_row_fields';

    /**
     * Possible module field types.
     */
    public const TYPE_INPUT_TEXT = 'input_text';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'module_row_id',
        'label',
        'module_field_type',
        'required',
        'position',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'required' => 'boolean',
    ];

    /**
     * Get the module row associated with the module.
     *
     * @return BelongsTo
     */
    public function row(): BelongsTo
    {
        return $this->belongsTo(ModuleRow::class, 'module_row_id');
    }
}
