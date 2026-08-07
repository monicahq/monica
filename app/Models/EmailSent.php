<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\EmailSentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class EmailSent
 *
 * Represents an email that has been sent in the system.
 *
 * @property int $id
 * @property int $company_id
 * @property int|null $user_id
 * @property string|null $uuid
 * @property string $email_type
 * @property string $email_address
 * @property string|null $subject
 * @property string|null $body
 * @property Carbon|null $sent_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $bounced_at
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class EmailSent extends Model
{
    /** @use HasFactory<EmailSentFactory> */
    use HasFactory;

    protected $table = 'emails_sent';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'user_id',
        'uuid',
        'email_type',
        'email_address',
        'subject',
        'body',
        'sent_at',
        'delivered_at',
        'bounced_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'bounced_at' => 'datetime',
        ];
    }

    /**
     * Get the company the email was sent on behalf of.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who received the email, when they have an account.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
