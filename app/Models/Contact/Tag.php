<?php

namespace App\Models\Contact;

use App\Models\Account\Account;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Get the account record associated with the tag.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contacts record associated with the tag.
     */
    public function contacts()
    {
        return $this->belongsToMany(Contact::class)->withPivot('account_id')->withTimestamps();
    }

    /**
     * Update the slug.
     */
    public function updateSlug()
    {
        $this->name_slug = str_slug($this->name);
        $this->save();
    }
}
