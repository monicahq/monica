<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TemplateAppliesToEnum;
use Carbon\CarbonImmutable;
use Database\Factories\VaultDefaultTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Class VaultDefaultTemplate
 *
 * Which structure a new record gets, in this vault, for this kind of record.
 *
 * One entity type may have several templates, and the one applied by default
 * differs from vault to vault: the family vault and a professional breeding
 * vault both handle Animals at completely different levels of detail. The same
 * holds for contacts, since a work contact and a family contact do not start
 * from the same page.
 *
 * It is a table rather than a column on the vault because there is one default
 * per kind of record, not one per vault.
 *
 * The row points at a version and never at a template. Pointing at a template
 * would make a new record follow whatever was published last, which is the one
 * thing the versioning engine exists to prevent.
 *
 * @property int $id
 * @property int $account_id
 * @property int $vault_id
 * @property TemplateAppliesToEnum $applies_to
 * @property int|null $entity_type_id
 * @property int $template_version_id
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read Vault $vault
 * @property-read EntityType|null $entityType
 * @property-read TemplateVersion $templateVersion
 */
#[Fillable([
    'account_id',
    'vault_id',
    'applies_to',
    'entity_type_id',
    'template_version_id',
])]
class VaultDefaultTemplate extends Model
{
    /** @use HasFactory<VaultDefaultTemplateFactory> */
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
            'applies_to' => TemplateAppliesToEnum::class,
        ];
    }

    /**
     * Get the account the default belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the vault the default applies in.
     *
     * @return BelongsTo<Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the kind of record the default is for, which only a default for an
     * entity has.
     *
     * @return BelongsTo<EntityType, $this>
     */
    public function entityType(): BelongsTo
    {
        return $this->belongsTo(EntityType::class);
    }

    /**
     * Get the exact version a new record starts on.
     *
     * @return BelongsTo<TemplateVersion, $this>
     */
    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(TemplateVersion::class);
    }
}
