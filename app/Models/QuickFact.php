<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuickFact extends Model
{
    use HasFactory;

    protected $table = 'quick_facts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int,string>
     */
    protected $fillable = [
        'contact_id',
        'vault_quick_facts_template_id',
        'content',
    ];

    /**
     * Get the contact associated with the quick fact.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the quick fact template associated with the quick fact.
     */
    public function vaultQuickFactsTemplate(): BelongsTo
    {
        return $this->belongsTo(VaultQuickFactsTemplate::class);
    }
}
