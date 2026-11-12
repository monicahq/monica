<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Possession;
use Illuminate\Support\Facades\DB;

/**
 * The order of the featured possessions of a vault. It numbers them again from one, so that a
 * possession leaving the order, or deleted, leaves no gap, and places one of them where it was asked.
 */
class FeaturedPossessionOrder
{
    public static function arrange(int $vaultId, ?Possession $moved = null, ?int $position = null): void
    {
        $possessions = Possession::query()
            ->where('vault_id', $vaultId)
            ->where('is_featured', true)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        if ($moved !== null) {
            $possessions = $possessions->reject(fn (Possession $possession): bool => $possession->is($moved))->values();
            $possessions->splice(max(0, ($position ?? $possessions->count() + 1) - 1), 0, [$moved]);
        }

        DB::transaction(function () use ($possessions): void {
            $possessions->each(function (Possession $possession, int $index): void {
                if ($possession->position === $index + 1) {
                    return;
                }

                $possession->position = $index + 1;
                $possession->save();
            });
        });
    }
}
