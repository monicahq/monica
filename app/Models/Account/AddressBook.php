<?php

namespace App\Models\Account;

use App\Models\Contact\Contact;
use App\Models\User\User;
use function safe\json_decode;
use function safe\json_encode;
use Illuminate\Support\Facades\Crypt;
use App\Models\ModelBinding as Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AddressBook extends Model
{
    protected $table = 'addressbooks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'user_id',
        'name',
        'addressBookId',
        'uri',
        'capabilities',
        'username',
        'password',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];

    /**
     * Get the account record associated with the address book.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user record associated with the address book.
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all contacts for this address book.
     *
     * @return HasMany
     */
    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get capabilities.
     *
     * @param string $value
     * @return array
     */
    public function getCapabilitiesAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * Set capabilities.
     *
     * @param string $value
     * @return void
     */
    public function setCapabilitiesAttribute($value)
    {
        $this->attributes['capabilities'] = json_encode($value);
    }

    /**
     * Get password.
     *
     * @param string $value
     * @return string
     */
    public function getPasswordAttribute($value)
    {
        return Crypt::decryptString($value);
    }

    /**
     * Set password.
     *
     * @param string $value
     * @return void
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Crypt::encryptString($value);
    }
}
