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
 * Delete a vault and everything it separates from the rest. It is the most
 * destructive thing somebody can do short of deleting the account, so the
 * screen that calls this has to say what the vault holds before it does.
 *
 * The deletion is soft, and the vault can be brought back with
 * {@see RestoreVault} while it is still there to bring back. What the vault
 * held goes with it and comes back with it: the contacts and the records of the
 * invented kinds are marked as having left with the vault, which is how a
 * restore later tells them from something deleted on its own beforehand.
 *
 * The last vault of an account goes too. An account with no vault is a state
 * the product already knows, since it is the one every account starts in, and
 * whoever asked is taken back to it. The next vault they create is the default
 * one, because there is nothing else to be it.
 *
 * A vault that was the default gives the title up before leaving, both because
 * exactly one vault holds it and because a vault brought back later must not
 * come back holding it while another one does.
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

            if ($wasDefault) {
                $this->promoteTheFirstVaultLeft();
            }
        });
    }

    /**
     * Send the contacts out at the same moment as the vault, and give back the
     * counts they were holding on the versions they render from, exactly as
     * deleting one of them by hand does.
     *
     * They are marked as having left with the vault, so that a restore later
     * brings back these and not somebody who was deleted on their own an hour
     * earlier. The moment alone would not say it: two deletions a second apart
     * are written to the same second.
     *
     * Somebody already deleted is outside the soft delete scope and is not
     * touched, and comes back only on their own terms.
     */
    private function takeTheContactsWithIt(): void
    {
        $counts = Contact::query()
            ->where('vault_id', $this->vault->id)
            ->groupBy('template_version_id')
            ->selectRaw('template_version_id, count(*) as total')
            ->pluck('total', 'template_version_id');

        foreach ($counts as $versionId => $total) {
            TemplateVersion::query()
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

    /**
     * Send the records of the invented kinds out with the vault too, and give
     * back the counts they were holding, both on the versions they render from
     * and on the types they are records of.
     *
     * They are marked the same way the contacts are, for the same reason: a
     * restore later brings back these and not something deleted on its own an
     * hour earlier.
     */
    private function takeTheEntitiesWithIt(): void
    {
        $this->giveTheCountsBack('template_version_id', TemplateVersion::class, 'record_count');
        $this->giveTheCountsBack('entity_type_id', EntityType::class, 'entity_count');

        Entity::query()
            ->where('vault_id', $this->vault->id)
            ->update([
                'deleted_at' => $this->vault->deleted_at,
                'deleted_with_vault' => true,
            ]);
    }

    /**
     * @param  class-string<Model>  $model
     */
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

    /**
     * Hand the title to the first vault left, when there is one left to hand it
     * to. An account with no vault has no default, and needs none: nothing can
     * be created without a vault to create it in.
     */
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
