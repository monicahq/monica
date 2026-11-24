<?php

declare(strict_types=1);

namespace App\View\Components\Vault;

use App\Models\EntityType;
use App\Models\KnowledgeList;
use App\Models\KnowledgeNote;
use App\Models\Possession;
use App\Models\PossessionType;
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
    ) {}

    #[Override]
    public function render(): View
    {
        $showsThings = $this->vault->shows_things && PossessionType::query()->where('account_id', $this->vault->account_id)->exists();

        return view('components.vault.sidebar', [
            'reminderCount' => $this->reminderCount(),
            'entityTypes' => $this->vault->shows_people ? $this->entityTypes() : [],
            'showsThings' => $showsThings,
            'possessionCount' => $showsThings ? Possession::query()->where('vault_id', $this->vault->id)->count() : 0,
            'knowledgeCount' => $this->vault->shows_knowledge ? KnowledgeNote::query()->where('vault_id', $this->vault->id)->count() + KnowledgeList::query()->where('vault_id', $this->vault->id)->count() : 0,
            'searchShortcut' => $this->searchShortcut(),
        ]);
    }

    /**
     * The shortcut to search: Command K on an Apple device and Control K elsewhere, told apart by
     * the browser asking, so the key the column shows is the one the page listens for.
     *
     * @return array{url: string, usesCommandKey: bool, keys: string, label: string}
     */
    private function searchShortcut(): array
    {
        $usesCommandKey = preg_match('/Macintosh|Mac OS X|iPhone|iPad/', (string) request()->userAgent()) === 1;

        return [
            'url' => route('search.index', $this->vault),
            'usesCommandKey' => $usesCommandKey,
            'keys' => $usesCommandKey ? 'Meta+K' : 'Control+K',
            'label' => $usesCommandKey ? '⌘K' : __('Ctrl K'),
        ];
    }

    /** @return list<array{key: string, label: string, icon: string, url: string, badge: null, colour: string}> */
    private function entityTypes(): array
    {
        return EntityType::query()
            ->where('account_id', $this->vault->account_id)
            ->configuredInVault($this->vault)
            ->orderBy('position')
            ->get()
            ->map(fn (EntityType $type): array => [
                'key' => "entity-type-{$type->slug}",
                'label' => $type->name_plural,
                'icon' => $type->icon ?? EntityType::DEFAULT_ICON,
                'url' => route('entities.index', ['vault' => $this->vault, 'entityType' => $type]),
                'badge' => null,
                'colour' => $type->colour,
            ])
            ->all();
    }

    private function reminderCount(): int
    {
        return ReminderOccurrence::query()
            ->awaitingAnswerInVault(
                vault: $this->vault,
                untilDay: CarbonImmutable::now($this->user->timezone),
            )
            ->count();
    }
}
