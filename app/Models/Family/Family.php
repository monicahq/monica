<?php

namespace App\Models\Family;

use Carbon\Carbon;
use App\Helpers\DBHelper;
use App\Models\User\User;
use App\Traits\Searchable;
use Illuminate\Support\Str;
use App\Helpers\LocaleHelper;
use App\Models\Account\Photo;
use App\Models\Journal\Entry;
use function Safe\preg_split;
use App\Helpers\WeatherHelper;
use App\Models\Account\Account;
use App\Models\Account\Weather;
use App\Models\Account\Activity;
use function Safe\preg_match_all;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Instance\SpecialDate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use App\Models\Account\ActivityStatistic;
use App\Models\Relationship\Relationship;
use Illuminate\Database\Eloquent\Builder;
use App\Models\ModelBindingHasher as Model;
use App\Http\Resources\Tag\Tag as TagResource;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Http\Resources\Address\Address as AddressResource;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;
use App\Http\Resources\ContactField\ContactField as ContactFieldResource;

class Family extends Model
{
    protected $table = 'families';

    /**
     * The attributes that should be cast as dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'name',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the account associated with the family.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
