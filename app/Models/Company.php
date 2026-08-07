<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PlanEnum;
use App\Enums\SizeRangeEnum;
use App\Enums\WorkModeEnum;
use Carbon\Carbon;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Company
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $legal_name
 * @property string|null $logo_path
 * @property string|null $website_url
 * @property string|null $industry
 * @property SizeRangeEnum|null $size_range
 * @property Carbon|null $founded_at
 * @property string $timezone
 * @property string $locale
 * @property string|null $currency
 * @property WorkModeEnum|null $work_mode
 * @property PlanEnum $plan
 * @property bool $is_self_hosted
 * @property string|null $billing_email
 * @property Carbon|null $trial_ends_at
 * @property int|null $owner_user_id
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    protected $table = 'companies';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'legal_name',
        'logo_path',
        'website_url',
        'industry',
        'size_range',
        'founded_at',
        'timezone',
        'locale',
        'currency',
        'work_mode',
        'plan',
        'is_self_hosted',
        'billing_email',
        'trial_ends_at',
        'owner_user_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_range' => SizeRangeEnum::class,
            'work_mode' => WorkModeEnum::class,
            'plan' => PlanEnum::class,
            'founded_at' => 'date',
            'trial_ends_at' => 'datetime',
            'is_self_hosted' => 'boolean',
        ];
    }

    /**
     * Get the user who owns the company.
     *
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * Get the users who belong to the company.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get whether the company is still within its trial period.
     */
    public function isOnTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }
}
