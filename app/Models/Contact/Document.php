<?php

namespace App\Models\Contact;

use App\Helpers\StorageHelper;
use App\Models\Account\Account;
use App\Models\ModelBinding as Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'documents';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'number_of_downloads' => 'number',
    ];

    /**
     * Get the account record associated with the document.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contact record associated with the document.
     *
     * @return BelongsTo
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the downloadl link.
     *
     * @return string
     */
    public function getDownloadLink(): string
    {
        $url = $this->new_filename;

        return asset(StorageHelper::disk(config('filesystems.default'))->url($url));
    }
}
