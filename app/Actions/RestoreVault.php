<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Bring a deleted vault back, with everything it held still inside it.
 *
 * Deleting a vault is the most destructive thing somebody can do short of
 * deleting the account, which is why it is reversible. It comes back where it
 * was in the list, and with the contacts and the records that left with it:
 * those are the ones the deletion marked as having gone with it, and anything
 * deleted on its own beforehand stays deleted.
 *
 * It comes back as the default only when the account has nobody else to be it,
 * which is what happens when it was the last vault. Otherwise another vault
 * took the title on when this one left, and exactly one holds it.
 */
class RestoreVault
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
    ) {}

    public function execute(): Vault
    {
        $this->validate();
        $this->restore();
        $this->log();

        return $this->vault;
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }
    }

    private function restore(): void
    {
        DB::transaction(function (): void {
            $this->vault->restore();

            $this->bringBackTheContactsThatWentWithIt();
            $this->bringBackTheEntitiesThatWentWithIt();
            $this->takeTheTitleBackIfNobodyHasIt();
        });
    }

    private function bringBackTheContactsThatWentWithIt(): void
    {
        $counts = Contact::query()
            ->onlyTrashed()
            ->where('vault_id', $this->vault->id)
            ->where('deleted_with_vault', true)
            ->groupBy('template_version_id')
            ->selectRaw('template_version_id, count(*) as total')
            ->pluck('total', 'template_version_id');

        foreach ($counts as $versionId => $total) {
            TemplateVersion::query()->whereKey($versionId)->increment('record_count', $total);
        }

        Contact::query()
            ->onlyTrashed()
            ->where('vault_id', $this->vault->id)
            ->where('deleted_with_vault', true)
            ->update([
                'deleted_at' => null,
                'deleted_with_vault' => false,
            ]);
    }

    private function bringBackTheEntitiesThatWentWithIt(): void
    {
        $this->giveTheCountsBack('template_version_id', TemplateVersion::class, 'record_count');
        $this->giveTheCountsBack('entity_type_id', EntityType::class, 'entity_count');

        Entity::query()
            ->onlyTrashed()
            ->where('vault_id', $this->vault->id)
            ->where('deleted_with_vault', true)
            ->update([
                'deleted_at' => null,
                'deleted_with_vault' => false,
            ]);
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function giveTheCountsBack(string $column, string $model, string $counter): void
    {
        $counts = Entity::query()
            ->onlyTrashed()
            ->where('vault_id', $this->vault->id)
            ->where('deleted_with_vault', true)
            ->groupBy($column)
            ->selectRaw("{$column}, count(*) as total")
            ->pluck('total', $column);

        foreach ($counts as $id => $total) {
            $model::query()->whereKey($id)->increment($counter, $total);
        }
    }

    private function takeTheTitleBackIfNobodyHasIt(): void
    {
        $taken = Vault::query()
            ->where('account_id', $this->user->account_id)
            ->where('is_default', true)
            ->exists();

        if ($taken) {
            return;
        }

        $this->vault->is_default = true;
        $this->vault->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            vault: $this->vault,
            user: $this->user,
            action: UserActionEnum::VaultRestoration,
            parameters: ['name' => $this->vault->name],
            loggable: $this->vault,
        )->onQueue('low');
    }
}
