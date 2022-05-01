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
        'company_id',
        'job_position',
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
    public function toSearchableArray(): array
    {
        return [
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
    }

    /**
     * Used to delete related objects from Meilisearch/Algolia instance.
     *
     * @return void
     */
    protected static function boot(): void
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
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the gender associated with the contact.
     *
     * @return BelongsTo
     */
    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class);
    }

    /**
     * Get the pronoun associated with the contact.
     *
     * @return BelongsTo
     */
    public function pronoun(): BelongsTo
    {
        return $this->belongsTo(Pronoun::class);
    }

    /**
     * Get the template associated with the contact.
     *
     * @return BelongsTo
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * Get the contact log records associated with the contact.
     *
     * @return HasMany
     */
    public function contactLogs(): HasMany
    {
        return $this->hasMany(ContactLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the relationships associated with the contact.
     *
     * @return BelongsToMany
     */
    public function relationships(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'relationships', 'contact_id', 'related_contact_id');
    }

    /**
     * Get the labels associated with the contact.
     *
     * @return BelongsToMany
     */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class);
    }

    /**
     * Get the contact information records associated with the contact.
     *
     * @return HasMany
     */
    public function contactInformation(): HasMany
    {
        return $this->hasMany(ContactInformation::class);
    }

    /**
     * Get the address records associated with the contact.
     *
     * @return HasMany
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Get the note records associated with the contact.
     *
     * @return HasMany
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    /**
     * Get the date records associated with the contact.
     *
     * @return HasMany
     */
    public function dates(): HasMany
    {
        return $this->hasMany(ContactImportantDate::class);
    }

    /**
     * Get the contact reminders records associated with the contact.
     *
     * @return HasMany
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(ContactReminder::class);
    }

    /**
     * Get the loans associated with the contact as the person who takes the
     * loan.
     * I know loaner is not a real word, but it's the best I could come up with.
     *
     * @return BelongsToMany
     */
    public function loansAsLoaner()
    {
        return $this->belongsToMany(Loan::class, 'contact_loan', 'loaner_id');
    }

    /**
     * Get the loans associated with the contact as the person who takes the
     * loan.
     * I know loanee is not a real word, but it's the best I could come up with.
     *
     * @return BelongsToMany
     */
    public function loansAsLoanee()
    {
        return $this->belongsToMany(Loan::class, 'contact_loan', 'loanee_id');
    }

    /**
     * Get the company associated with the contact.
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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
