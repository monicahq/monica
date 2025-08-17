<?php

namespace App\Models;

use App\Domains\Contact\Dav\VCalendarResource;
use App\Traits\HasUuids;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ContactImportantDate extends VCalendarResource
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'contact_important_dates';

    /**
     * Possible types.
     */
    public const TYPE_BIRTHDATE = 'birthdate';

    public const TYPE_DECEASED_DATE = 'deceased_date';

    /**
     * Possible type of dates.
     */
    public const TYPE_FULL_DATE = 'full_date';

    public const TYPE_MONTH_DAY = 'month_day';

    public const TYPE_YEAR = 'year';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'contact_id',
        'label',
        'day',
        'month',
        'year',
        'contact_important_date_type_id',
        'vcalendar',
        'distant_uuid',
        'distant_etag',
        'distant_uri',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'day' => 'integer',
        'month' => 'integer',
        'year' => 'integer',
    ];

    /**
     * Get the contact associated with the contact date.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the important date type associated with the contact date.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\ContactImportantDateType, $this>
     */
    public function contactImportantDateType(): BelongsTo
    {
        return $this->belongsTo(ContactImportantDateType::class);
    }

    /**
     * Get the important date's feed item.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne<\App\Models\ContactFeedItem, $this>
     */
    public function feedItem(): MorphOne
    {
        return $this->morphOne(ContactFeedItem::class, 'feedable');
    }

    /**
     * Get the date as a Carbon instance.
     *
     * @return Attribute<Carbon,null>
     */
    public function date(): Attribute
    {
        return Attribute::get(function () {
            return Carbon::create($this->year, $this->month, $this->day);
        });
    }

    /**
     * Get the date as a VCard formatted string.
     *
     * @see https://datatracker.ietf.org/doc/html/rfc6350#section-6.2.5
     */
    public function getVCardDate(): string
    {
        $date = $this->year ? Str::padLeft((string) $this->year, 2, '0') : '--';
        if ($this->month === null && $this->day === null) {
            return $date;
        }

        $date .= $this->month ? Str::padLeft((string) $this->month, 2, '0') : '-';
        $date .= $this->day ? Str::padLeft((string) $this->day, 2, '0') : '';

        return $date;
    }

    /**
     * Scope a query to only include active subscriptions.
     */
    #[Scope]
    public function birthday(Builder $query): Builder
    {
        return $query
            ->where('contact_important_date_type_id', function (Builder $query) {
                $query->select('id')
                    ->from('contact_important_date_types')
                    ->whereColumn('internal_type', ContactImportantDate::TYPE_BIRTHDATE)
                    ->limit(1);
            });
    }
}
