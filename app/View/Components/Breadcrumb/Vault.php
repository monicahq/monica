<?php

declare(strict_types=1);

namespace App\View\Components\Breadcrumb;

use App\Models\Vault as VaultModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;
use Override;

class Vault extends Component
{
    /** @param array<int, array{label: string, url?: string|null}> $items */
    public function __construct(
        public VaultModel $vault,
        public array $items = [],
    ) {}

    #[Override]
    public function render(): View
    {
        return view('components.breadcrumb.vault', [
            'vaults' => $this->vaults(),
        ]);
    }

    /** @return Collection<int, VaultModel> */
    private function vaults(): Collection
    {
        return VaultModel::query()
            ->where('account_id', $this->vault->account_id)
            ->orderByDesc('is_default')
            ->orderBy('position')
            ->get();
    }
}
