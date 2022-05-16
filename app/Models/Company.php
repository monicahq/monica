<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Company extends Model
{
    use HasFactory;

    protected $table = 'companies';

    /**
     * Possible module types.
     */
    const TYPE_COMPANY = 'company';
    const TYPE_ORGANIZATION = 'organization';
    const TYPE_ASSOCIATION = 'association';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'vault_id',
        'name',
        'type',
    ];

    /**
     * Get the vault associated with the company.
     *
     * @return BelongsTo
     */
    public function vault()
    {
        return $this->belongsTo(Vault::class);
    }
}
