<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Information extends Model
{
    use HasFactory;

    protected $table = 'information';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'name',
        'allows_multiple_entries',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'allows_multiple_entries' => 'boolean',
    ];

    /**
     * Get the account associated with the information.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the attribute values associated with the information.
     *
     * @return hasMany
     */
    public function attributes()
    {
        return $this->hasMany(Attribute::class);
    }

    /**
     * Get the templates associated with the information.
     *
     * @return belongsToMany
     */
    public function templates()
    {
        return $this->belongsToMany(Template::class);
    }
}
