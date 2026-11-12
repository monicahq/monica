<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInVault;
use Carbon\CarbonImmutable;
use Database\Factories\KnowledgeNoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class KnowledgeNote
 *
 * Free text somebody wants to remember, written in Markdown, inside one
 * folder of a vault's Knowledge: where the water shutoff is, the plan for a
 * trip. Nothing else attaches to it yet: it links to no contact, no
 * possession and no other note.
 *
 * The text is shown as Markdown and never executed: raw HTML is escaped and
 * unsafe links are refused when it is drawn.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $vault_id
 * @property int $knowledge_folder_id
 * @property string $title
 * @property string|null $body
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read KnowledgeFolder $folder
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'vault_id',
    'knowledge_folder_id',
    'title',
    'body',
])]
class KnowledgeNote extends Model
{
    public const int MAX_BODY_LENGTH = 200000;

    /** @use HasFactory<KnowledgeNoteFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInVault;

    /**
     * Get the folder the note sits in.
     *
     * @return BelongsTo<KnowledgeFolder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(KnowledgeFolder::class, 'knowledge_folder_id');
    }
}
