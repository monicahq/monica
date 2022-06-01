<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pet extends Model
{
    use HasFactory;

    protected $table = 'pets';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'pet_category_id',
        'contact_id',
        'name',
    ];

    /**
     * Get the pet category associated with the pet.
     *
     * @return BelongsTo
     */
    public function petCategory(): BelongsTo
    {
        return $this->belongsTo(PetCategory::class);
    }

    /**
     * Get the contact associated with the pet.
     *
     * @return BelongsTo
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
