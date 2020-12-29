<?php

namespace App\Models\Account;

use App\Models\User\User;
use function safe\json_decode;
use function safe\json_encode;
use App\Models\ModelBinding as Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AddressBookSubscription extends Model
{
    protected $table = 'addressbook_subscriptions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'user_id',
        'addressbook_id',
        'name',
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
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['lastsync'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];

    /**
     * Get the account record associated with the subscription.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user record associated with the subscription.
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the addressbook record associated with the subscription.
     *
     * @return BelongsTo
     */
    public function addressbook()
    {
        return $this->belongsTo(AddressBook::class);
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
