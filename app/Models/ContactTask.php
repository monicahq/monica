<?php

namespace App\Models;

use App\Domains\Contact\Dav\VCardResource;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactTask extends VCardResource
{
    use HasFactory, HasUuids;
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

    public function scopeNotCompleted($query)
    {
        return $query->where('completed', false);
    }

    public function scopeCompleted($query)
    {
        return $query->where('completed', true);
    }
}
