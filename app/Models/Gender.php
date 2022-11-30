<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gender extends Model
{
    use HasFactory;

    /**
     * Male type gender.
     *
     * @var string
     */
    public const MALE = 'M';

    /**
     * Female type gender.
     *
     * @var string
     */
    public const FEMALE = 'F';

    /**
     * Other type gender.
     *
     * @var string
     */
    public const OTHER = 'O';

    /**
     * Unknown type gender.
     *
     * @var string
     */
    public const UNKNOWN = 'U';

    /**
     * None type gender.
     *
     * @var string
     */
    public const NONE = 'N';

    public const LIST = ['M', 'F', 'O', 'U', 'N'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'account_id',
        'name',
        'type',
    ];

    /**
     * Get the account associated with the gender.
     *
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
