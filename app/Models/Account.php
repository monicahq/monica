<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PlanEnum;
use Carbon\Carbon;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Account
 *
 * What everything in the application belongs to. An account holds users, and
 * every one of them can do the same things.
 *
 * @property int $id
 * @property PlanEnum $plan
 * @property bool $is_self_hosted
 * @property string|null $billing_email
 * @property Carbon|null $trial_ends_at
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory;

    protected $table = 'accounts';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'plan',
        'is_self_hosted',
        'billing_email',
        'trial_ends_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'plan' => PlanEnum::class,
            'trial_ends_at' => 'datetime',
            'is_self_hosted' => 'boolean',
        ];
    }

    /**
     * Get the users who belong to the account.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get whether the account is still within its trial period.
     */
    public function isOnTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }
}
