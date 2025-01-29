<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Pet extends Model
{
    use HasFactory;

    protected $table = 'pets';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'pet_category_id',
        'contact_id',
        'name',
    ];

    /**
     * Get the pet category associated with the pet.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\PetCategory, $this>
     */
    public function petCategory(): BelongsTo
    {
        return $this->belongsTo(PetCategory::class);
    }

    /**
     * Get the contact associated with the pet.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the pet's feed item.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne<\App\Models\ContactFeedItem, $this>
     */
    public function feedItem(): MorphOne
    {
        return $this->morphOne(ContactFeedItem::class, 'feedable');
    }
}
