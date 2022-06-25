<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Laravel\Scout\Searchable;

class Loan extends Model
{
    use HasFactory;
    use Searchable;

    /**
     * Possible types.
     */
    public const TYPE_DEBT = 'debt';
    public const TYPE_LOAN = 'loan';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'vault_id',
        'type',
        'name',
        'description',
        'amount_lent',
        'currency_id',
        'loaned_at',
        'settled_at',
        'settled',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'loaned_at',
        'settled_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'settled' => 'boolean',
    ];

    /**
     * Get the vault associated with the loan.
     *
     * @return BelongsTo
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the currency associated with the loan.
     *
     * @return BelongsTo
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the contact that did the loan.
     *
     * @return BelongsToMany
     */
    public function loaners(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_loan', 'loan_id', 'loaner_id');
    }

    /**
     * Get the contact records the loan was made to.
     *
     * @return BelongsToMany
     */
    public function loanees(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_loan', 'loan_id', 'loanee_id');
    }

    /**
     * Get the loan's feed item.
     */
    public function feedItem(): MorphOne
    {
        return $this->morphOne(ContactFeedItem::class, 'feedable');
    }
}
