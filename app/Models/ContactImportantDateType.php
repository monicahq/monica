<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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
     * @var list<string>
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
     * @var array<string, string>
     */
    protected $casts = [
        'can_be_deleted' => 'boolean',
    ];

    /**
     * Get the vault associated with the contact date type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the birthday date types.
     */
    #[Scope]
    public function birthday(Builder $query): Builder
    {
        return $query->where('internal_type', ContactImportantDate::TYPE_BIRTHDATE);
    }
}
