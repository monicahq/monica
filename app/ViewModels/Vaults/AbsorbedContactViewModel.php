<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;

class AbsorbedContactViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $absorbed,
        private readonly Contact $kept,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function title(): string
    {
        return __('This record was merged');
    }

    public function explanation(): string
    {
        return __(':absorbed was merged into :kept, so this address no longer opens a record of its own.', [
            'absorbed' => $this->absorbed->name,
            'kept' => $this->kept->name,
        ]);
    }

    public function keptName(): string
    {
        return $this->kept->name;
    }

    public function keptUrl(): string
    {
        return route('contacts.show', ['vault' => $this->vault, 'contact' => $this->kept]);
    }

    public function peopleUrl(): string
    {
        return route('contacts.index', ['vault' => $this->vault]);
    }
}
