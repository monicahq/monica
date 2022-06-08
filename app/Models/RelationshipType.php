<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Factories\Relationship;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelationshipType extends Model
{
    use HasFactory;

    protected $table = 'relationship_types';

    /**
     * Possible types.
     */
    const TYPE_LOVE = 'family';
    const TYPE_CHILD = 'child';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'relationship_group_type_id',
        'name',
        'name_reverse_relationship',
        'can_be_deleted',
        'type',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'can_be_deleted' => 'boolean',
    ];

    /**
     * Get the group type associated with the relationship type.
     *
     * @return BelongsTo
     */
    public function groupType(): BelongsTo
    {
        return $this->belongsTo(RelationshipGroupType::class, 'relationship_group_type_id');
    }
}
