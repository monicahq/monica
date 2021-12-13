<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Factories\Relationship;

class RelationshipType extends Model
{
    use HasFactory;

    protected $table = 'relationship_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'relationship_group_type_id',
        'name',
        'name_reverse_relationship',
    ];

    /**
     * Get the group type associated with the relationship type.
     *
     * @return BelongsTo
     */
    public function groupType()
    {
        return $this->belongsTo(RelationshipGroupType::class, 'relationship_group_type_id');
    }
}
