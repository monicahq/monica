<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'author_id',
        'author_name',
        'action_name',
        'objects',
    ];

    /**
     * Get the account associated with the audit log.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user associated with the audit log.
     *
     * @return BelongsTo
     */
    public function author()
    {
        return $this->BelongsTo(User::class);
    }

    /**
     * Get the JSON object.
     *
     * @param  mixed  $value
     * @return mixed
     */
    public function getObjectAttribute($value)
    {
        return json_decode($this->objects);
    }
}
