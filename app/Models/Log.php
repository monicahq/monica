<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserActionEnum;
use Carbon\Carbon;
use Database\Factories\LogFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Log
 *
 * Represents an action a user performed in a company.
 *
 * @property int $id
 * @property int $company_id
 * @property int|null $user_id
 * @property string $user_email
 * @property string $action
 * @property array<string, mixed>|null $parameters
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property-read string $author
 * @property-read string $description
 */
class Log extends Model
{
    /** @use HasFactory<LogFactory> */
    use HasFactory;

    protected $table = 'logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'user_id',
        'user_email',
        'action',
        'parameters',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parameters' => 'array',
        ];
    }

    /**
     * Get the company the action was performed in.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who performed the action, as long as they still exist.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get who performed the action. A log outlives the account behind it, so it
     * falls back to the email address recorded at the time once that account,
     * or the employee record under it, is gone.
     *
     * @return Attribute<string, never>
     */
    protected function author(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->user->employee->name ?? $this->user_email,
        );
    }

    /**
     * Get what the action reads as, in the language of whoever is looking. An
     * action this version of the application no longer knows about is shown by
     * its raw name rather than hidden.
     *
     * @return Attribute<string, never>
     */
    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn (): string => __(
                UserActionEnum::tryFrom($this->action)?->description() ?? $this->action,
                $this->parameters ?? [],
            ),
        );
    }
}
