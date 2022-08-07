<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactLog extends Model
{
    use HasFactory;

    protected $table = 'contact_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'contact_id',
        'author_id',
        'author_name',
        'action_name',
        'objects',
    ];

    /**
     * Get the contact associated with the contact log.
     *
     * @return BelongsTo
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the user associated with the contact log.
     *
     * @return BelongsTo
     */
    public function author(): BelongsTo
    {
        return $this->BelongsTo(User::class);
    }

    /**
     * Get the JSON object.
     *
     * @param  mixed  $value
     * @return mixed
     */
    public function getObjectAttribute($value): mixed
    {
        return json_decode($this->objects);
    }
}
