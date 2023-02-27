<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PetCategory extends Model
{
    use HasFactory;

    protected $table = 'pet_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'account_id',
        'name',
    ];

    /**
     * Get the account associated with the pet category.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
