<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Security;

use App\Helpers\DeviceDescription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use stdClass;

class SessionListViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly string $currentSessionId,
    ) {}

    public function canList(): bool
    {
        return config('session.driver') === 'database';
    }

    /** @return list<array{id: string, device: string, address: ?string, lastUsedAt: string, isCurrent: bool}> */
    public function sessions(): array
    {
        $since = now()->subMinutes((int) config('session.lifetime'))->getTimestamp();

        $sessions = DB::table(config('session.table'))
            ->where('user_id', $this->user->id)
            ->where('last_activity', '>=', $since)
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn (stdClass $session): array => [
                'id' => (string) $session->id,
                'device' => DeviceDescription::fromUserAgent($session->user_agent),
                'address' => $session->ip_address,
                'lastUsedAt' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                'isCurrent' => $session->id === $this->currentSessionId,
            ]);

        [$current, $others] = $sessions->partition(fn (array $session): bool => $session['isCurrent']);

        return $current->concat($others)->values()->all();
    }

    public function header(): string
    {
        return trans_choice('Signed in on :count device|Signed in on :count devices', count($this->sessions()));
    }
}
