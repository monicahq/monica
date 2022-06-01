<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vault extends Model
{
    use HasFactory;

    /**
     * Possible vault permissions.
     */
    const PERMISSION_VIEW = 300;
    const PERMISSION_EDIT = 200;
    const PERMISSION_MANAGE = 100;

    /**
     * Possible vault types.
     */
    const TYPE_PERSONAL = 'personal';
    const TYPE_FAMILY = 'family';
    const TYPE_COMMUNITY = 'community';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'type',
        'name',
        'description',
        'default_template_id',
    ];

    /**
     * Used to delete related objects from Meilisearch/Algolia instance.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($model) {
            if ($model->contacts) {
                foreach ($model->contacts as $contact) {
                    Note::where('contact_id', $contact->id)->unsearchable();
                }
                Contact::where('vault_id', $model->id)->unsearchable();
            }
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
        return $this->belongsToMany(User::class)->withTimestamps()->withPivot('permission');
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
}
