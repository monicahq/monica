<?php

declare(strict_types=1);

namespace App\View\Components\Vault;

use App\Models\ContactDuplicateCandidate;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault as VaultModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
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
            'duplicateCount' => $this->duplicateCount(),
            'vaults' => $this->vaults(),
        ]);
    }

    /**
     * How many pairs are waiting to be looked at. A dismissed pair is not one of
     * them, so the badge goes quiet once somebody has been through the screen.
     */
    private function duplicateCount(): int
    {
        return ContactDuplicateCandidate::query()
            ->standing()
            ->where('vault_id', $this->vault->id)
            ->count();
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

    /** @return Collection<int, VaultModel> */
    private function vaults(): Collection
    {
        return VaultModel::query()
            ->where('account_id', $this->user->account_id)
            ->orderByDesc('is_default')
            ->orderBy('position')
            ->get();
    }
}
