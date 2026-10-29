<?php

declare(strict_types=1);

namespace App\Http\Dav;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Support\Str;
use Override;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

/**
 * A move the standard cannot perform becomes a copy and a removal. The removal refuses the contact
 * representing the person, which would leave the copy behind and that person in two vaults, so a
 * move whose source is that contact is refused before either half happens.
 */
class MoveGuard extends ServerPlugin
{
    public function __construct(
        private readonly User $user,
        private readonly AddressBookBackend $backend,
    ) {}

    #[Override]
    public function initialize(Server $server): void
    {
        $server->on('beforeMove', $this->refuseWhatCannotBeUndone(...));
    }

    #[Override]
    public function getPluginName(): string
    {
        return 'move-guard';
    }

    private function refuseWhatCannotBeUndone(string $source, string $destination): bool
    {
        if ($this->contactAt($source)?->is_me !== true) {
            return true;
        }

        throw new Forbidden('The contact representing you cannot be moved from a device.');
    }

    private function contactAt(string $path): ?Contact
    {
        $parts = explode('/', trim($path, '/'));

        if (count($parts) !== 4 || $parts[0] !== 'addressbooks') {
            return null;
        }

        if (! Str::isUuid($parts[2])) {
            return null;
        }

        $vault = Vault::query()
            ->where('account_id', $this->user->account_id)
            ->where('uuid', $parts[2])
            ->first();

        if ($vault === null) {
            return null;
        }

        return Contact::query()
            ->where('vault_id', $vault->id)
            ->get()
            ->first(fn (Contact $contact): bool => $this->backend->uriOf($contact) === $parts[3]);
    }
}
