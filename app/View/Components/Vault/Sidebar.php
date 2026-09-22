<?php

declare(strict_types=1);

namespace App\View\Components\Vault;

use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault as VaultModel;
use Carbon\CarbonImmutable;
use Illuminate\View\Component;
use Illuminate\View\View;
use Override;

class Sidebar extends Component
{
    public function __construct(
        public User $user,
        public VaultModel $vault,
        public string $current = 'dashboard',
        public ?int $taskCount = null,
    ) {}

    #[Override]
    public function render(): View
    {
        return view('components.vault.sidebar', [
            'reminderCount' => $this->reminderCount(),
        ]);
    }

    private function reminderCount(): int
    {
        return ReminderOccurrence::query()
            ->upcomingInVault(
                vault: $this->vault,
                until: CarbonImmutable::now()->addDays((int) config('monica.reminder_generation_window_in_days')),
            )
            ->count();
    }
}
