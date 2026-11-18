<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Models\StripeEvent;
use Carbon\CarbonImmutable;

class StripeEvents
{
    /** @return list<array{kind: string, lastSevenDays: int, lastThirtyDays: int}> */
    public function countsByKind(): array
    {
        $since = now()->subDays(StripeEvent::RETENTION_IN_DAYS);
        $sevenDaysAgo = now()->subDays(7);
        $counts = [];

        foreach (StripeEvent::query()->where('created_at', '>=', $since)->orderBy('type')->get(['type', 'created_at']) as $event) {
            $counts[$event->type] ??= ['kind' => $event->type, 'lastSevenDays' => 0, 'lastThirtyDays' => 0];
            $counts[$event->type]['lastThirtyDays']++;

            if ($event->created_at->greaterThanOrEqualTo($sevenDaysAgo)) {
                $counts[$event->type]['lastSevenDays']++;
            }
        }

        return array_values($counts);
    }

    /** @return array{kind: string, receivedAt: CarbonImmutable}|null */
    public function latest(): ?array
    {
        $event = StripeEvent::query()
            ->where('created_at', '>=', now()->subDays(StripeEvent::RETENTION_IN_DAYS))
            ->latest()
            ->latest('id')
            ->first();

        return $event === null ? null : ['kind' => $event->type, 'receivedAt' => $event->created_at];
    }

    public function isEmpty(): bool
    {
        return $this->latest() === null;
    }
}
