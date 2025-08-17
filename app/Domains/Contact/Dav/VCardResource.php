<?php

namespace App\Domains\Contact\Dav;

use AllowDynamicProperties;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property ?string $vault_id
 * @property ?string $distant_etag
 * @property ?string $vcard
 * @property ?Carbon $updated_at
 * @property ?Carbon $created_at
 * @property ?Carbon $deleted_at
 */
#[AllowDynamicProperties]
abstract class VCardResource extends Model implements IDavResource
{
    use SoftDeletes;
}
