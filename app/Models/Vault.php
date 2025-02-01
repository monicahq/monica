<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vault extends Model
{
    use HasFactory, HasUuids;

    /**
     * Possible vault permissions.
     */
    public const PERMISSION_VIEW = 300;

    public const PERMISSION_EDIT = 200;

    public const PERMISSION_MANAGE = 100;

    /**
     * Possible vault types.
     */
    public const TYPE_PERSONAL = 'personal';

    public const TYPE_FAMILY = 'family';

    public const TYPE_COMMUNITY = 'community';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'type',
        'name',
        'description',
        'default_template_id',
        'default_activity_tab',
        'show_group_tab',
        'show_tasks_tab',
        'show_files_tab',
        'show_journal_tab',
        'show_companies_tab',
        'show_calendar_tab',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'show_group_tab' => 'boolean',
        'show_calendar_tab' => 'boolean',
        'show_tasks_tab' => 'boolean',
        'show_files_tab' => 'boolean',
        'show_journal_tab' => 'boolean',
        'show_companies_tab' => 'boolean',
        'show_reports_tab' => 'boolean',
    ];

    /**
     * Used to delete related objects from scout driver instance.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (self $model) {
            foreach ($model->contacts as $contact) {
                $contact->notes()->unsearchable(); // @phpstan-ignore-line
            }
            $model->contacts()->unsearchable(); // @phpstan-ignore-line
        });
    }

    /**
     * Get the account associated with the vault.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the template associated with the vault.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Template, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class, 'default_template_id');
    }

    /**
     * Get the contact associated with the vault.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Contact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get the labels associated with the vault.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Label, $this>
     */
    public function labels(): HasMany
    {
        return $this->hasMany(Label::class);
    }

    /**
     * Get the users associated with the vault.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('permission', 'contact_id')
            ->withTimestamps();
    }

    /**
     * Get the contact important date types associated with the vault.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ContactImportantDateType, $this>
     */
    public function contactImportantDateTypes(): HasMany
    {
        return $this->hasMany(ContactImportantDateType::class);
    }

    /**
     * Get the companies associated with the vault.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Company, $this>
     */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    /**
     * Get the groups associated with the vault.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Group, $this>
     */
    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    /**
     * Get the journals associated with the vault.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Journal, $this>
     */
    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }

    /**
     * Get the tags associated with the vault.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Tag, $this>
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * Get the loans associated with the vault.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Loan, $this>
     */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Get the files associated with the vault.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\File, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    /**
     * Get the mood tracking parameters associated with the vault.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\MoodTrackingParameter, $this>
     */
    public function moodTrackingParameters(): HasMany
    {
        return $this->hasMany(MoodTrackingParameter::class);
    }

    /**
     * Get the life event categories associated with the vault.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\LifeEventCategory, $this>
     */
    public function lifeEventCategories(): HasMany
    {
        return $this->hasMany(LifeEventCategory::class);
    }

    /**
     * Get the timeline events associated with the vault.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\TimelineEvent, $this>
     */
    public function timelineEvents(): HasMany
    {
        return $this->hasMany(TimelineEvent::class);
    }

    /**
     * Get the address records associated with the vault.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Address, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Get the quick fact template entries records associated with the vault.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\VaultQuickFactsTemplate, $this>
     */
    public function quickFactsTemplateEntries(): HasMany
    {
        return $this->hasMany(VaultQuickFactsTemplate::class);
    }

    /**
     * Get the life metric records associated with the vault.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\LifeMetric, $this>
     */
    public function lifeMetrics(): HasMany
    {
        return $this->hasMany(LifeMetric::class);
    }
}
