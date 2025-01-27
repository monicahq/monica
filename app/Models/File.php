<?php

namespace App\Models;

use App\Domains\Contact\ManageDocuments\Events\FileDeleted;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $fileable_type
 * @property int $fileable_id
 */
class File extends Model
{
    use HasFactory;

    protected $table = 'files';

    /**
     * Possible type.
     */
    public const TYPE_DOCUMENT = 'document';

    public const TYPE_AVATAR = 'avatar';

    public const TYPE_PHOTO = 'photo';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vault_id',
        'fileable_id',
        'fileable_type',
        'ufileable_id',
        'uuid',
        'original_url',
        'cdn_url',
        'name',
        'mime_type',
        'type',
        'size',
    ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'deleted' => FileDeleted::class,
    ];

    /**
     * Get the vault associated with the file.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the parent model that matches this file.
     */
    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the parent model that matches this file.
     */
    public function ufileable(): MorphTo
    {
        return $this->morphTo(type: 'fileable_type');
    }
}
