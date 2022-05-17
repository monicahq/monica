<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable, HasFactory, HasApiTokens;

    /**
     * Possible number format types.
     */
    const NUMBER_FORMAT_TYPE_COMMA_THOUSANDS_DOT_DECIMAL = '1,234.56';
    const NUMBER_FORMAT_TYPE_SPACE_THOUSANDS_COMMA_DECIMAL = '1 234,56';
    const NUMBER_FORMAT_TYPE_NO_SPACE_DOT_DECIMAL = '1234.56';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'first_name',
        'last_name',
        'email',
        'email_verified_at',
        'password',
        'is_account_administrator',
        'invitation_code',
        'invitation_accepted_at',
        'name_order',
        'date_format',
        'number_format',
        'timezone',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'invitation_accepted_at' => 'datetime',
        'is_account_administrator' => 'boolean',
    ];

    /**
     * Get the account record associated with the user.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the vault records associated with the user.
     *
     * @return BelongsToMany
     */
    public function vaults()
    {
        return $this->belongsToMany(Vault::class)->withTimestamps()->withPivot('permission');
    }

    /**
     * Get the note records associated with the user.
     *
     * @return HasMany
     */
    public function notes()
    {
        return $this->hasMany(Note::class, 'author_id');
    }

    /**
     * Get the notification channel records associated with the user.
     *
     * @return HasMany
     */
    public function notificationChannels()
    {
        return $this->hasMany(UserNotificationChannel::class, 'user_id');
    }

    /**
     * Get the task records associated with the user.
     *
     * @return HasMany
     */
    public function contactTasks()
    {
        return $this->hasMany(ContactTask::class, 'author_id');
    }

    /**
     * Get the name of the user.
     *
     * @param  mixed  $value
     * @return null|string
     */
    public function getNameAttribute($value): ?string
    {
        if (! $this->first_name) {
            return null;
        }

        return $this->first_name.' '.$this->last_name;
    }

    /**
     * Get the contact of the user in the given vault.
     * All users have a contact in the vaults.
     *
     * @param  Vault  $vault
     * @return Contact
     */
    public function getContactInVault(Vault $vault): Contact
    {
        $contact = DB::table('user_vault')->where('vault_id', $vault->id)
            ->where('user_id', $this->id)
            ->first();

        return Contact::findOrFail($contact->contact_id);
    }
}
