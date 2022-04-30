<?php

namespace App\Models\Contact;

use App\Traits\HasUuid;
use App\Models\Account\Account;
use App\Models\ModelBinding as Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gender extends Model
{
    use HasUuid;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'type',
        'account_id',
    ];

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
     * Get the account record associated with the gender.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contact records associated with the gender.
     *
     * @return HasMany
     */
    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Is this gender the default account one?.
     *
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->account->default_gender_id === $this->id;
    }
}
