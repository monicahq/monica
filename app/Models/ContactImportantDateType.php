<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactImportantDateType extends Model
{
    use HasFactory;

    protected $table = 'contact_important_date_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'vault_id',
        'label',
        'internal_type',
        'can_be_deleted',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'can_be_deleted' => 'boolean',
    ];

    /**
     * Get the vault associated with the contact date type.
     *
     * @return BelongsTo
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }
}
