<?php

namespace App\Models\Account;

use App\Models\Contact\Occupation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Company extends Model
{
    protected $table = 'companies';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'weather_json',
        'account_id',
        'name',
        'website',
        'number_of_employees',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the account record associated with the company.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the Occupation records associated with the contact.
     *
     * @return HasMany
     */
    public function occupations()
    {
        return $this->hasMany(Occupation::class);
    }
}
