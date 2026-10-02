<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\LifeEvent;
use App\Models\ShareLink;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Delete a vault and everything it separates from the rest.
 */
class DestroyVault
{
    private string $name;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }
    }

    private function destroy(): void
    {
        $this->name = $this->vault->name;
        $wasDefault = $this->vault->is_default;

        DB::transaction(function () use ($wasDefault): void {
            $this->vault->is_default = false;
            $this->vault->save();
            $this->vault->delete();

            $this->takeTheContactsWithIt();
            $this->takeTheEntitiesWithIt();
            $this->revokeTheLinksOntoIt();

            if ($wasDefault) {
                $this->promoteTheFirstVaultLeft();
            }
        });

        Contact::withdrawFromSearch(Contact::withTrashed()->where('vault_id', $this->vault->id));
        Entity::withdrawFromSearch(Entity::withTrashed()->where('vault_id', $this->vault->id));
        LifeEvent::withdrawFromSearch(LifeEvent::withTrashed()->where('vault_id', $this->vault->id));
    }

    private function takeTheContactsWithIt(): void
    {
        $counts = Contact::query()
            ->where('vault_id', $this->vault->id)
            ->groupBy('structure_version_id')
            ->selectRaw('structure_version_id, count(*) as total')
            ->pluck('total', 'structure_version_id');

        foreach ($counts as $versionId => $total) {
            StructureVersion::query()
                ->whereKey($versionId)
                ->where('record_count', '>=', $total)
                ->decrement('record_count', $total);
        }

        Contact::query()
            ->where('vault_id', $this->vault->id)
            ->update([
                'deleted_at' => $this->vault->deleted_at,
                'deleted_with_vault' => true,
            ]);
    }

    private function takeTheEntitiesWithIt(): void
    {
        $this->giveTheCountsBack('structure_version_id', StructureVersion::class, 'record_count');
        $this->giveTheCountsBack('entity_type_id', EntityType::class, 'entity_count');

        Entity::query()
            ->where('vault_id', $this->vault->id)
            ->update([
                'deleted_at' => $this->vault->deleted_at,
                'deleted_with_vault' => true,
            ]);
    }

    /** @param  class-string<Model>  $model */
    private function giveTheCountsBack(string $column, string $model, string $counter): void
    {
        $counts = Entity::query()
            ->where('vault_id', $this->vault->id)
            ->groupBy($column)
            ->selectRaw("{$column}, count(*) as total")
            ->pluck('total', $column);

        foreach ($counts as $id => $total) {
            $model::query()
                ->whereKey($id)
                ->where($counter, '>=', $total)
                ->decrement($counter, $total);
        }
    }

    private function revokeTheLinksOntoIt(): void
    {
        ShareLink::query()
            ->where('vault_id', $this->vault->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    private function promoteTheFirstVaultLeft(): void
    {
        $vault = Vault::query()
            ->where('account_id', $this->user->account_id)
            ->orderBy('position')
            ->first();

        if ($vault === null) {
            return;
        }

        $vault->is_default = true;
        $vault->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            vault: $this->vault,
            user: $this->user,
            action: UserActionEnum::VaultDeletion,
            parameters: ['name' => $this->name],
            loggable: $this->vault,
        )->onQueue('low');
    }
}
