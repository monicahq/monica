<?php

namespace App\Domains\Contact\Dav;

use AllowDynamicProperties;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property ?string $vcalendar
 * @property ?Carbon $updated_at
 * @property ?Carbon $created_at
 * @property ?Carbon $deleted_at
 */
#[AllowDynamicProperties]
abstract class VCalendarResource extends Model implements IDavResource
{
    use SoftDeletes;
}
