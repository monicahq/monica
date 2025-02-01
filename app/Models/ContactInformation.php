<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class ContactInformation extends Model
{
    use HasFactory;

    protected $table = 'contact_information';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'contact_id',
        'type_id',
        'data',
    ];

    /**
     * Get the contact associated with the contact information.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the contact information type associated with the contact information.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\ContactInformationType, $this>
     */
    public function contactInformationType(): BelongsTo
    {
        return $this->belongsTo(ContactInformationType::class, 'type_id');
    }

    /**
     * Get the content of the contact information.
     * If the contact information type is a phone number or an email, return the
     * content. If it's something else, return the contact information type's label.
     *
     * @return Attribute<string,string>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $type = $this->contactInformationType;

                if (! $type->can_be_deleted) {
                    return $this->data;
                } else {
                    return $type->name;
                }
            },
            set: fn ($value) => $value,
        );
    }

    /**
     * Get the contact information's feed item.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne<\App\Models\ContactFeedItem, $this>
     */
    public function feedItem(): MorphOne
    {
        return $this->morphOne(ContactFeedItem::class, 'feedable');
    }
}
