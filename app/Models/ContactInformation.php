<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactInformation extends Model
{
    use HasFactory;

    protected $table = 'contact_information';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'contact_id',
        'type_id',
        'data',
    ];

    /**
     * Get the contact associated with the contact information.
     *
     * @return BelongsTo
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the contact information type associated with the contact information.
     *
     * @return BelongsTo
     */
    public function contactInformationType(): BelongsTo
    {
        return $this->belongsTo(ContactInformationType::class, 'type_id');
    }
}
