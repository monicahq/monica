<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\Group;
use App\Models\User;
use App\Models\Vault;

class GroupFilesViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Group $group,
    ) {}

    public function header(): GroupViewModel
    {
        return once(fn (): GroupViewModel => new GroupViewModel(user: $this->user, vault: $this->vault, group: $this->group));
    }

    public function files(): GroupFilesBlock
    {
        return once(fn (): GroupFilesBlock => new GroupFilesBlock(user: $this->user, vault: $this->vault, group: $this->group));
    }
}
