<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactInformationType extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'name',
        'name_translation_key',
        'protocol',
        'type', // used to make sure phone and email fields can't be deleted, and also to find the email address of the contact quickly
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
     * Get the account associated with the contact information type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the name of the Contact information type attribute.
     * Contact information type entries have a default name that can be translated.
     * Howerer, if a name is set, it will be used instead of the default.
     *
     * @return Attribute<string,string>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if ($value === null || $value == '') {
                    return __($attributes['name_translation_key']);
                }

                return $value;
            },
            set: fn ($value) => $value,
        );
    }
}
