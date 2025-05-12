<?php

namespace App\Models;

use App\Domains\Contact\Dav\VCardResource;
use App\Helpers\AvatarHelper;
use App\Helpers\ContactImportantDateHelper;
use App\Helpers\ImportantDateHelper;
use App\Helpers\NameHelper;
use App\Helpers\ScoutHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Laravel\Scout\Searchable;

class Contact extends VCardResource
{
    use HasFactory, HasUuids;
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
     * @var list<string>
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
        'show_quick_facts',
        'template_id',
        'last_updated_at',
        'company_id',
        'job_position',
        'listed',
        'file_id',
        'religion_id',
        'vcard',
        'distant_uuid',
        'distant_etag',
        'distant_uri',
        'prefix',
        'suffix',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'vault_id' => 'string',
        'can_be_deleted' => 'boolean',
        'listed' => 'boolean',
        'show_quick_facts' => 'boolean',
        'last_updated_at' => 'datetime',
    ];

    /**
     * Get the indexable data array for the model.
     *
     * @codeCoverageIgnore
     */
    #[SearchUsingFullText(['first_name', 'last_name', 'middle_name', 'nickname', 'maiden_name'], ['expanded' => true])]
    public function toSearchableArray(): array
    {
        return array_merge(ScoutHelper::id($this), [
            'first_name' => $this->first_name ?? '',
            'last_name' => $this->last_name ?? '',
            'middle_name' => $this->middle_name ?? '',
            'nickname' => $this->nickname ?? '',
            'maiden_name' => $this->maiden_name ?? '',
        ]);
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
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('listed', 1);
    }

    /**
     * Used to delete related objects from scout driver instance.
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
        return ScoutHelper::isActivated();
    }

    /**
     * Get the vault associated with the contact.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the gender associated with the contact.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Gender, $this>
     */
    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class);
    }

    /**
     * Get the pronoun associated with the contact.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Pronoun, $this>
     */
    public function pronoun(): BelongsTo
    {
        return $this->belongsTo(Pronoun::class);
    }

    /**
     * Get the template associated with the contact.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Template, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * Get the relationships associated with the contact.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Contact, $this>
     */
    public function relationships(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'relationships', 'contact_id', 'related_contact_id');
    }

    /**
     * Get the labels associated with the contact.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Label, $this>
     */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class);
    }

    /**
     * Get the contact information records associated with the contact.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ContactInformation, $this>
     */
    public function contactInformations(): HasMany
    {
        return $this->hasMany(ContactInformation::class);
    }

    /**
     * Get the note records associated with the contact.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Note, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    /**
     * Get the date records associated with the contact.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ContactImportantDate, $this>
     */
    public function importantDates(): HasMany
    {
        return $this->hasMany(ContactImportantDate::class);
    }

    /**
     * Get the contact reminders records associated with the contact.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ContactReminder, $this>
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Loan, $this>
     */
    public function loansAsLoaner(): BelongsToMany
    {
        return $this->belongsToMany(Loan::class, 'contact_loan', 'loaner_id');
    }

    /**
     * Get the loans associated with the contact as the person who takes the
     * loan.
     * I know loanee is not a real word, but it's the best I could come up with.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Loan, $this>
     */
    public function loansAsLoanee(): BelongsToMany
    {
        return $this->belongsToMany(Loan::class, 'contact_loan', 'loanee_id');
    }

    /**
     * Get the company associated with the contact.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the tasks associated with the contact.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ContactTask, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(ContactTask::class);
    }

    /**
     * Get the calls associated with the contact.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Call, $this>
     */
    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    /**
     * Get the pets associated with the contact.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Pet, $this>
     */
    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class);
    }

    /**
     * Get the goals associated with the contact.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Goal, $this>
     */
    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    /**
     * Get the files associated with the contact.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<\App\Models\File, $this>
     */
    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'ufileable', 'fileable_type');
    }

    /**
     * Get the file associated with the contact.
     * If it exists, it's the avatar.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\File, $this>
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    /**
     * Get the groups associated with the contact.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Group, $this>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class);
    }

    /**
     * Get the posts associated with the contact.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Post, $this>
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    /**
     * Get the religion associated with the contact.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Religion, $this>
     */
    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class);
    }

    /**
     * Get the life events associated with the contact.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\LifeEvent, $this>
     */
    public function lifeEvents(): BelongsToMany
    {
        return $this->belongsToMany(LifeEvent::class, 'life_event_participants', 'contact_id', 'life_event_id');
    }

    /**
     * Get the timeline events associated with the contact.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\TimelineEvent, $this>
     */
    public function timelineEvents(): BelongsToMany
    {
        return $this->belongsToMany(TimelineEvent::class, 'timeline_event_participants', 'contact_id', 'timeline_event_id');
    }

    /**
     * Get the mood tracking events associated with the contact.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\MoodTrackingEvent, $this>
     */
    public function moodTrackingEvents(): HasMany
    {
        return $this->hasMany(MoodTrackingEvent::class);
    }

    /**
     * Get the addresses associated with the contact.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Address, $this>
     */
    public function addresses(): BelongsToMany
    {
        return $this->belongsToMany(Address::class, 'contact_address')
            ->withPivot('is_past_address')
            ->withTimestamps();
    }

    /**
     * Get the quick facts associated with the contact.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\QuickFact, $this>
     */
    public function quickFacts(): HasMany
    {
        return $this->hasMany(QuickFact::class);
    }

    /**
     * Get the life metrics associated with the contact.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\LifeMetric, $this>
     */
    public function lifeMetrics(): BelongsToMany
    {
        return $this->belongsToMany(LifeMetric::class, 'contact_life_metric', 'contact_id', 'life_metric_id')->withTimestamps();
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

                $firstName = Arr::get($attributes, 'first_name');
                $lastName = Arr::get($attributes, 'last_name');
                $separator = $firstName && $lastName ? ' ' : '';

                return $firstName.$separator.$lastName;
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
                    ->firstWhere('contact_important_date_type_id', $type->id);

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
