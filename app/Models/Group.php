<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DatePrecisionEnum;
use App\Helpers\ReservedAddressWords;
use App\Helpers\SlugBuilder;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInVault;
use Carbon\CarbonImmutable;
use Database\Factories\GroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

/**
 * Class Group
 *
 * A set of contacts that exists apart from their relationships: a household, a couple, a family, a
 * team, a circle of friends. It is one of the fixed concepts, so it has no kind, no template and no
 * field of its own, only a name, a description and the dates it existed between.
 *
 * Who is in it is recorded by memberships, one per period somebody belonged, so a person who left
 * and came back holds two. A deleted contact's memberships stay in place and are hidden with them.
 *
 * A group is addressed two ways, and answers to both. The slug is built from its name and rebuilt
 * when the name changes, so the one it had stops answering. The uuid never changes and never stops
 * answering, which is what the API, the MCP server and the group's card name it by.
 *
 * A group is also a card in the vault's address book, so a device that syncs the vault sees it and
 * can change its name and its current members.
 *
 * @property int $id
 * @property string $uuid
 * @property string $slug
 * @property int $account_id
 * @property int $vault_id
 * @property string $name
 * @property string|null $description
 * @property CarbonImmutable|null $started_on
 * @property DatePrecisionEnum $started_precision
 * @property CarbonImmutable|null $ended_on
 * @property DatePrecisionEnum $ended_precision
 * @property string|null $dav_uri
 * @property bool $deleted_with_vault
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Account $account
 * @property-read Vault $vault
 * @property-read Collection<int, GroupMember> $members
 * @property-read Collection<int, GroupMember> $currentMembers
 */
#[RouteKey('slug')]
#[Fillable([
    'account_id',
    'vault_id',
    'slug',
    'name',
    'description',
    'started_on',
    'started_precision',
    'ended_on',
    'ended_precision',
    'dav_uri',
    'deleted_with_vault',
])]
class Group extends Model
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInVault;
    use SoftDeletes;

    public const int SLUG_LENGTH = 128;

    public const string SLUG_WHEN_EMPTY = 'group';

    #[Override]
    protected static function booted(): void
    {
        static::saving(function (Group $group): void {
            if (($group->slug ?? '') === '' || $group->isDirty('name')) {
                $group->slug = SlugBuilder::readable(
                    derivedFrom: $group->name,
                    maxLength: self::SLUG_LENGTH,
                    whenEmpty: self::SLUG_WHEN_EMPTY,
                    isTaken: fn (string $slug): bool => $group->slugIsTaken($slug),
                );
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'started_on' => 'date',
            'started_precision' => DatePrecisionEnum::class,
            'ended_on' => 'date',
            'ended_precision' => DatePrecisionEnum::class,
            'deleted_with_vault' => 'boolean',
        ];
    }

    /**
     * Get the account the group belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the vault it lives in.
     *
     * @return BelongsTo<Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get every membership, current and former.
     *
     * @return HasMany<GroupMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    /**
     * Get the memberships of the people in the group today. A membership whose contact is deleted is
     * left out, and comes back when the contact is restored.
     *
     * @return HasMany<GroupMember, $this>
     */
    public function currentMembers(): HasMany
    {
        return $this->hasMany(GroupMember::class)
            ->whereNull('left_on')
            ->whereHas('contact');
    }

    /**
     * Scope the query to the groups of one vault.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeInVault(Builder $query, Vault $vault): void
    {
        $query->where('account_id', $vault->account_id)->where('vault_id', $vault->id);
    }

    /**
     * The two spellings of the group's address, the readable one first.
     *
     * @return list<string>
     */
    protected function routeKeyColumns(): array
    {
        return ['slug', 'uuid'];
    }

    /**
     * The address the product builds: the readable name unless the account asked for the identifier
     * instead. Both spellings keep answering either way.
     */
    #[Override]
    public function getRouteKey(): mixed
    {
        return Account::buildsReadableAddressesFor($this->account_id) ? $this->slug : $this->uuid;
    }

    /**
     * Whether another group of this vault answers to the slug, a deleted one included since it can be
     * brought back, or a route already fixes it.
     */
    private function slugIsTaken(string $slug): bool
    {
        if (in_array($slug, ReservedAddressWords::forGroup(), true)) {
            return true;
        }

        return self::withTrashed()
            ->where('vault_id', $this->vault_id)
            ->where('slug', $slug)
            ->when($this->id !== null, fn (Builder $query) => $query->where('id', '!=', $this->id))
            ->exists();
    }
}
