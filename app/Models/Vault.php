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
     * @var array<string>
     */
    protected $fillable = [
        'account_id',
        'type',
        'name',
        'description',
        'default_template_id',
        'show_group_tab',
        'show_tasks_tab',
        'show_files_tab',
        'show_journal_tab',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'show_group_tab' => 'boolean',
        'show_tasks_tab' => 'boolean',
        'show_files_tab' => 'boolean',
        'show_journal_tab' => 'boolean',
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
     * Used to delete related objects from Meilisearch/Algolia instance.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function (self $model) {
            foreach ($model->contacts as $contact) {
                $contact->notes()->unsearchable();
            }
            $model->contacts()->unsearchable();
        });
    }

    /**
     * Get the account associated with the vault.
     *
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the template associated with the vault.
     *
     * @return BelongsTo
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class, 'default_template_id');
    }

    /**
     * Get the contact associated with the vault.
     *
     * @return HasMany
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get the labels associated with the vault.
     *
     * @return HasMany
     */
    public function labels(): HasMany
    {
        return $this->hasMany(Label::class);
    }

    /**
     * Get the users associated with the vault.
     *
     * @return BelongsToMany
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
     * @return HasMany
     */
    public function contactImportantDateTypes(): HasMany
    {
        return $this->hasMany(ContactImportantDateType::class);
    }

    /**
     * Get the companies associated with the vault.
     *
     * @return HasMany
     */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    /**
     * Get the groups associated with the vault.
     *
     * @return HasMany
     */
    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    /**
     * Get the journals associated with the vault.
     *
     * @return HasMany
     */
    public function journals()
    {
        return $this->hasMany(Journal::class);
    }

    /**
     * Get the tags associated with the vault.
     *
     * @return HasMany
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * Get the loans associated with the vault.
     *
     * @return HasMany
     */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}
