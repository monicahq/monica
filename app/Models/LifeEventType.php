<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInAccount;
use Carbon\CarbonImmutable;
use Database\Factories\LifeEventTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

/**
 * Class LifeEventType
 *
 * The nature of a life event: lunch, phone call, concert, medical appointment.
 *
 * The life event itself is a fixed product object. Its type is the configurable
 * part, and what it configures is a label, a presentation and a set of custom
 * fields. Those fields go through the same engine a template uses, which is why
 * this model owns a structure rather than growing a field mechanism of its own.
 *
 * The word the screens use for that structure is custom fields. Neither
 * template nor structure nor version reaches the user here, because neither is
 * what somebody came to this screen to manage.
 *
 * Whether its life events update Last in touch is set here too. A call or a
 * meal is a moment shared with the people it is recorded with and moves the
 * date their page shows; a move or a new job is news about one of them and
 * leaves it.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $life_event_category_id
 * @property string|null $key
 * @property string $name
 * @property string|null $name_translation_key
 * @property string|null $description
 * @property string|null $icon
 * @property string|null $colour
 * @property bool $is_system
 * @property bool $is_active
 * @property bool $updates_last_in_touch
 * @property int $position
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Account $account
 * @property-read LifeEventCategory $lifeEventCategory
 * @property-read Structure|null $structure
 */
#[Fillable([
    'account_id',
    'life_event_category_id',
    'key',
    'name',
    'name_translation_key',
    'description',
    'icon',
    'colour',
    'is_system',
    'is_active',
    'updates_last_in_touch',
    'position',
])]
class LifeEventType extends Model
{
    /** @use HasFactory<LifeEventTypeFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInAccount;
    use SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'updates_last_in_touch' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * Get the account the type belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the category the type is filed under.
     *
     * @return BelongsTo<LifeEventCategory, $this>
     */
    public function lifeEventCategory(): BelongsTo
    {
        return $this->belongsTo(LifeEventCategory::class);
    }

    /**
     * Get the field structure the type owns.
     *
     * Creating a type creates it, with one published version holding one empty
     * page, so a type nobody has given fields to still renders a life event
     * rather than failing to render one.
     *
     * @return MorphOne<Structure, $this>
     */
    public function structure(): MorphOne
    {
        return $this->morphOne(Structure::class, 'owner');
    }

    /**
     * Get what the type is called. One the product supplied has no name of its
     * own until somebody gives it one, and reads in the language of whoever is
     * looking until then.
     *
     * @return Attribute<string, string>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): string => $value ?? __((string) $this->name_translation_key),
        );
    }
}
