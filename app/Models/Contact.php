<?php

namespace App\Models;

use App\Helpers\NameHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contact extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'vault_id',
        'gender_id',
        'pronoun_id',
        'first_name',
        'last_name',
        'middle_name',
        'surname',
        'maiden_name',
        'can_be_deleted',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'can_be_deleted' => 'boolean',
    ];

    /**
     * Get the vault associated with the contact.
     *
     * @return BelongsTo
     */
    public function vault()
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the gender associated with the contact.
     *
     * @return BelongsTo
     */
    public function gender()
    {
        return $this->belongsTo(Gender::class);
    }

    /**
     * Get the pronoun associated with the contact.
     *
     * @return BelongsTo
     */
    public function pronoun()
    {
        return $this->belongsTo(Pronoun::class);
    }

    /**
     * Get the contact log records associated with the contact.
     *
     * @return HasMany
     */
    public function contactLogs()
    {
        return $this->hasMany(ContactLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the relationships associated with the contact.
     *
     * @return BelongsToMany
     */
    public function relationships()
    {
        return $this->belongsToMany(Contact::class, 'relationships', 'contact_id', 'related_contact_id');
    }

    /**
     * Get the labels associated with the contact.
     *
     * @return BelongsToMany
     */
    public function labels()
    {
        return $this->belongsToMany(Label::class);
    }

    /**
     * Get the contact information records associated with the contact.
     *
     * @return HasMany
     */
    public function contactInformation()
    {
        return $this->hasMany(ContactInformation::class);
    }

    /**
     * Get the contact address records associated with the contact.
     *
     * @return HasMany
     */
    public function contactAddresses()
    {
        return $this->hasMany(ContactAddress::class);
    }

    /**
     * Get the note records associated with the contact.
     *
     * @return HasMany
     */
    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    /**
     * Get the name of the contact, according to the user preference.
     *
     * @param  User  $user
     * @return string
     */
    public function getName(User $user): string
    {
        return NameHelper::formatContactName($user, $this);
    }
}
