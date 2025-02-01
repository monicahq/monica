<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gender extends Model
{
    use HasFactory;

    /**
     * Male type gender.
     *
     * @var string
     */
    public const MALE = 'M';

    /**
     * Female type gender.
     *
     * @var string
     */
    public const FEMALE = 'F';

    /**
     * Other type gender.
     *
     * @var string
     */
    public const OTHER = 'O';

    /**
     * Unknown type gender.
     *
     * @var string
     */
    public const UNKNOWN = 'U';

    /**
     * None type gender.
     *
     * @var string
     */
    public const NONE = 'N';

    public const LIST = ['M', 'F', 'O', 'U', 'N'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'name',
        'name_translation_key',
        'type',
    ];

    /**
     * Get the account associated with the gender.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the name attribute.
     * Genders have a default name that can be translated.
     * Howerer, if a name is set, it will be used instead of the default.
     *
     * @return Attribute<string,string>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if (! $value) {
                    return __($attributes['name_translation_key']);
                }

                return $value;
            },
            set: fn ($value) => $value,
        );
    }
}
