<?php

namespace App\Models;

use App\Helpers\NameHelper;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contact extends Model
{
    use HasFactory, Searchable;

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
        'nickname',
        'maiden_name',
        'can_be_deleted',
        'template_id',
        'last_updated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'can_be_deleted' => 'boolean',
        'last_updated_at' => 'datetime',
    ];

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        $array = [
            'id' => $this->id,
            'vault_id' => $this->vault_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'middle_name' => $this->middle_name,
            'nickname' => $this->nickname,
            'maiden_name' => $this->maiden_name,
            'url' => route('contact.show', [
                'vault' => $this->vault_id,
                'contact' => $this->id,
            ]),
        ];

        return $array;
    }

    /**
     * Used to delete related objects from Meilisearch/Algolia instance.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($model) {
            Note::where('contact_id', $model->id)->unsearchable();
        });
    }

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
     * Get the template associated with the contact.
     *
     * @return BelongsTo
     */
    public function template()
    {
        return $this->belongsTo(Template::class);
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
     * Get the address records associated with the contact.
     *
     * @return HasMany
     */
    public function addresses()
    {
        return $this->hasMany(Address::class);
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
     * Get the date records associated with the contact.
     *
     * @return HasMany
     */
    public function dates()
    {
        return $this->hasMany(ContactImportantDate::class);
    }

    /**
     * Get the contact reminders records associated with the contact.
     *
     * @return HasMany
     */
    public function reminders()
    {
        return $this->hasMany(ContactReminder::class);
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
