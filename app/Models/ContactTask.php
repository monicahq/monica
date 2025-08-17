<?php

namespace App\Models;

use App\Domains\Contact\Dav\VCalendarResource;
use App\Traits\HasUuids;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactTask extends VCalendarResource
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'contact_tasks';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'contact_id',
        'author_id',
        'author_name',
        'label',
        'description',
        'completed',
        'completed_at',
        'due_at',
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
        'completed' => 'boolean',
        'completed_at' => 'datetime',
        'due_at' => 'datetime',
    ];

    /**
     * Get the contact associated with the contact task.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the author associated with the contact task.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    #[Scope]
    public function notCompleted($query)
    {
        return $query->where('completed', false);
    }

    #[Scope]
    public function completed($query)
    {
        return $query->where('completed', true);
    }
}
