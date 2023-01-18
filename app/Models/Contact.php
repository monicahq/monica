<?php

namespace App\Models;

use App\Helpers\AvatarHelper;
use App\Helpers\ContactImportantDateHelper;
use App\Helpers\ImportantDateHelper;
use App\Helpers\NameHelper;
use App\Helpers\ScoutHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Laravel\Scout\Attributes\SearchUsingPrefix;
use Laravel\Scout\Searchable;

class Contact extends Model
{
    use HasFactory, SoftDeletes, HasUuids;
    use Searchable;
    use SoftDeletes;

    /**
     * Possible avatar types.
     */
    public const AVATAR_TYPE_SVG = 'svg';

    public const AVATAR_TYPE_URL = 'url';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
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
        'listed',
        'file_id',
        'religion_id',
        'vcard',
        'distant_etag',
        'prefix',
        'suffix',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'can_be_deleted' => 'boolean',
        'listed' => 'boolean',
        'last_updated_at' => 'datetime',
    ];

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     *
     * @codeCoverageIgnore
     */
    #[SearchUsingPrefix(['id', 'vault_id'])]
    #[SearchUsingFullText(['first_name', 'last_name', 'middle_name', 'nickname', 'maiden_name'])]
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
        ];
    }

    /**
     * Determine if the model should be searchable.
     *
     * @return bool
     */
    public function shouldBeSearchable()
    {
        return $this->listed;
    }

    /**
     * Scope a query to only include contacts who are active.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('listed', 1);
    }

    /**
     * Used to delete related objects from Meilisearch/Algolia instance.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (self $model) {
            $model->notes()->unsearchable();
        });
    }

    /**
     * When updating a model, this method determines if we should update the search index.
     *
     * @return bool
     */
    public function searchIndexShouldBeUpdated()
    {
        return ScoutHelper::activated();
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
    public function contactInformations(): HasMany
    {
        return $this->hasMany(ContactInformation::class);
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
    public function importantDates(): HasMany
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
     * Get the tasks associated with the contact.
     *
     * @return HasMany
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(ContactTask::class);
    }

    /**
     * Get the calls associated with the contact.
     *
     * @return HasMany
     */
    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    /**
     * Get the pets associated with the contact.
     *
     * @return HasMany
     */
    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class);
    }

    /**
     * Get the goals associated with the contact.
     *
     * @return HasMany
     */
    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    /**
     * Get the files associated with the contact.
     *
     * @return MorphMany
     */
    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    /**
     * Get the file associated with the contact.
     * If it exists, it's the avatar.
     *
     * @return BelongsTo
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    /**
     * Get the groups associated with the contact.
     *
     * @return BelongsToMany
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'contact_group');
    }

    /**
     * Get the posts associated with the contact.
     *
     * @return BelongsToMany
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'contact_post');
    }

    /**
     * Get the religion associated with the contact.
     *
     * @return BelongsTo
     */
    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class);
    }

    /**
     * Get the life events associated with the contact.
     *
     * @return BelongsToMany
     */
    public function lifeEvents(): BelongsToMany
    {
        return $this->belongsToMany(LifeEvent::class, 'life_event_participants', 'contact_id', 'life_event_id');
    }

    /**
     * Get the addresses associated with the contact.
     *
     * @return BelongsToMany
     */
    public function addresses(): BelongsToMany
    {
        return $this->belongsToMany(Address::class, 'contact_address', 'contact_id')->withPivot('is_past_address')->withTimestamps();
    }

    /**
     * Get the name of the contact, according to the user preference.
     *
     * @return Attribute<string,never>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if (Auth::check()) {
                    return NameHelper::formatContactName(Auth::user(), $this);
                }

                return $attributes['first_name'].' '.$attributes['last_name'];
            }
        );
    }

    /**
     * Get the age of the contact.
     * The birthdate is stored in a ContactImportantDate object, of the
     * TYPE_BIRTHDATE type. So we need to find if a date of this type exists.
     *
     * @return Attribute<?int,never>
     */
    protected function age(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $type = ContactImportantDateHelper::getImportantDateType(ContactImportantDate::TYPE_BIRTHDATE, $this->vault_id);

                if (! $type) {
                    return null;
                }

                $birthdate = $this->importantDates
                    ->where('contact_important_date_type_id', $type->id)
                    ->first();

                if (! $birthdate) {
                    return null;
                }

                return ImportantDateHelper::getAge($birthdate);
            }
        );
    }

    /**
     * Get the avatar of the contact.
     *
     * @return Attribute<array,never>
     */
    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $type = self::AVATAR_TYPE_SVG;
                $content = AvatarHelper::generateRandomAvatar($this);

                if ($this->file) {
                    $type = self::AVATAR_TYPE_URL;
                    $content = 'https://ucarecdn.com/'.$this->file->uuid.'/-/scale_crop/300x300/smart/-/format/auto/-/quality/smart_retina/';
                }

                return [
                    'type' => $type,
                    'content' => $content,
                ];
            }
        );
    }
}
