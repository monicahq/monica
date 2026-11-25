<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\DestroyTrashItem;
use App\Enums\TrashItemKindEnum;
use App\Helpers\TrashItem;
use App\Helpers\VaultTrash;
use App\Models\Vault;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Delete for good whatever has waited in the trash of a vault longer than the retention period, with
 * what went to the trash with it. Once a day is enough: the screen promises a day, not an hour, and it
 * runs before the orphaned files are swept so the files it lets go of start their own grace period. An
 * item already gone with something purged before it is skipped, and one that fails is reported without
 * stopping the others.
 */
class PurgeVaultTrash implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $cutoff = now()->subDays((int) config('monica.trash_retention_in_days'));

        Vault::query()
            ->whereIn('id', $this->vaultsHoldingTrashOlderThan($cutoff))
            ->with('account.user')
            ->lazyById()
            ->each(function (Vault $vault) use ($cutoff): void {
                $user = $vault->account->user;

                if ($user === null) {
                    return;
                }

                new VaultTrash(vault: $vault)
                    ->expiredBefore($cutoff)
                    ->each(function (TrashItem $item) use ($user, $vault): void {
                        try {
                            new DestroyTrashItem(
                                user: $user,
                                vault: $vault,
                                kind: $item->kind,
                                uuid: $item->uuid(),
                                writesToLog: false,
                            )->execute();
                        } catch (ModelNotFoundException) {
                            return;
                        } catch (Throwable $e) {
                            report($e);
                        }
                    });
            });
    }

    /** @return list<int> */
    private function vaultsHoldingTrashOlderThan(DateTimeInterface $cutoff): array
    {
        $ids = [];

        foreach (TrashItemKindEnum::cases() as $kind) {
            $table = new ($kind->model())()->getTable();

            $query = in_array($kind, [TrashItemKindEnum::GroupMembership, TrashItemKindEnum::GroupFile], true)
                ? DB::table($table)->join('groups', 'groups.id', '=', "{$table}.group_id")->select('groups.vault_id')
                : DB::table($table)->select("{$table}.vault_id");

            $ids = [...$ids, ...$query
                ->whereNotNull("{$table}.deleted_at")
                ->where("{$table}.deleted_at", '<=', $cutoff)
                ->distinct()
                ->pluck('vault_id')
                ->all()];
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }
}
