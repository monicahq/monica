<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactListColumnSourceEnum;
use Carbon\CarbonImmutable;
use Database\Factories\ContactListColumnFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Class ContactListColumn
 *
 * One column of the table of people in a vault, after the name, which is
 * always there and always first.
 *
 * A vault holds no rows until somebody chooses its columns, and until then the
 * list shows the defaults named in ContactListColumnSourceEnum. Once somebody
 * saves, the vault holds every column it shows, each in its place.
 *
 * A column showing a field of a template names the section and the key of the
 * field, never the field itself. Sections are shared between templates, and a
 * new version of a section copies its fields under new ids and the same keys,
 * so the section and the key are the only identity that survives both.
 *
 * @property int $id
 * @property int $account_id
 * @property int $vault_id
 * @property ContactListColumnSourceEnum $source
 * @property int|null $section_id
 * @property string|null $field_key
 * @property string|null $heading
 * @property int $position
 * @property bool $is_visible
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read Vault $vault
 * @property-read Section|null $section
 */
#[Fillable([
    'account_id',
    'vault_id',
    'source',
    'section_id',
    'field_key',
    'heading',
    'position',
    'is_visible',
])]
class ContactListColumn extends Model
{
    /** @use HasFactory<ContactListColumnFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'source' => ContactListColumnSourceEnum::class,
            'position' => 'integer',
            'is_visible' => 'boolean',
        ];
    }

    /**
     * Get the account the column belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the vault whose list shows the column.
     *
     * @return BelongsTo<Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the section holding the field the column shows, which only a column
     * showing a template field has. A deleted section is still returned, so
     * that the column can say it is no longer supplied.
     *
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class)->withTrashed();
    }
}
