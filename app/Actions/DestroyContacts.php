<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

/**
 * Delete several contacts of one vault at once. Each goes exactly as it would one by one, a contact
 * that may not be deleted is kept, with the reason, and one already deleted is skipped.
 */
class DestroyContacts
{
    public const int LIMIT = 500;

    /** @var list<Contact> */
    private array $deleted = [];

    /** @var list<array{contact: Contact, reason: string}> */
    private array $kept = [];

    /** @param  list<Contact>  $contacts */
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly array $contacts,
    ) {}

    /** @return array{deleted: list<Contact>, kept: list<array{contact: Contact, reason: string}>} */
    public function execute(): array
    {
        $this->validate();
        $this->destroy();

        return ['deleted' => $this->deleted, 'kept' => $this->kept];
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if (count($this->contacts) > self::LIMIT) {
            throw new InvalidArgumentException('At most 500 contacts can be deleted at once.');
        }

        foreach ($this->contacts as $contact) {
            if ($contact->vault_id !== $this->vault->id) {
                throw new ModelNotFoundException('This contact is in another vault.');
            }
        }
    }

    private function destroy(): void
    {
        foreach ($this->contacts as $contact) {
            if ($contact->trashed()) {
                continue;
            }

            try {
                new DestroyContact(user: $this->user, contact: $contact)->execute();
            } catch (AuthorizationException $e) {
                $this->kept[] = ['contact' => $contact, 'reason' => $e->getMessage()];

                continue;
            } catch (ModelNotFoundException) {
                continue;
            }

            $this->deleted[] = $contact;
        }
    }
}
