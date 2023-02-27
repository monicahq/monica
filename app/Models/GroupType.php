<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupType extends Model
{
    use HasFactory;

    protected $table = 'group_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'account_id',
        'label',
        'position',
    ];

    /**
     * Get the account record associated with the group type.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the group type roles associated with the group type.
     */
    public function groupTypeRoles(): HasMany
    {
        return $this->hasMany(GroupTypeRole::class);
    }
}
