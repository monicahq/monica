<?php

namespace App\Models\Contact;

use App\Models\Account\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'name_slug',
        'account_id',
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
     * Get the tags with the contact count.
     */
    public static function contactsCount()
    {
        return DB::table('contact_tag')->selectRaw('COUNT(tag_id) AS contact_count, name, tag_id AS id')
                    ->join('tags', function ($join) {
                        $join->on('tags.id', '=', 'contact_tag.tag_id')
                           ->on('tags.account_id', '=', 'contact_tag.account_id');
                    })
                    ->join('contacts', function ($join) {
                        $join->on('contacts.id', '=', 'contact_tag.contact_id')
                           ->on('contacts.account_id', '=', 'contact_tag.account_id');
                    })
                    ->where([
                        'tags.account_id' => auth()->user()->account_id,
                        'contacts.address_book_id' => null,
                    ])
                    ->groupBy('tag_id')
                    ->get()
                    ->sortByCollator('name');
    }
}
