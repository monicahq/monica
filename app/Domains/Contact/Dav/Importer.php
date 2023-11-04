<?php

namespace App\Domains\Contact\Dav;

use App\Domains\Contact\Dav\Services\ImportVCard;
use App\Models\Account;
use App\Models\User;
use App\Models\Vault;
use Ramsey\Uuid\Uuid;
use Sabre\VObject\Component\VCard;

abstract class Importer implements ImportVCardResource
{
    public ImportVCard $context;

    /**
     * Can import Card.
     */
    public function can(VCard $vcard): bool
    {
        return true;
    }

    /**
     * Set context.
     */
    public function setContext(ImportVCard $context): ImportVCardResource
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Get the account.
     */
    protected function account(): Account
    {
        return $this->vault()->account;
    }

    /**
     * Get the vault.
     */
    protected function vault(): Vault
    {
        return $this->context->vault;
    }

    /**
     * Get the author.
     */
    protected function author(): User
    {
        return $this->context->author;
    }

    /**
     * Formats and returns a string for DAV Card/Cal.
     */
    protected function formatValue(?string $value): ?string
    {
        return ! empty($value) ? str_replace('\;', ';', trim($value)) : null;
    }

    /**
     * Get uid of the card.
     */
    protected function getUid(VCard $entry): ?string
    {
        if (! empty($uuid = (string) $entry->UID)) {
            return $uuid;
        }

        return null;
    }

    /**
     * Import UID.
     */
    protected function importUid(array $data, VCard $entry): array
    {
        if (($uuid = $this->getUid($entry)) !== null && Uuid::isValid($uuid) && ! $this->context->external) {
            $data['id'] = $uuid;
        }

        return $data;
    }

    protected function kind(VCard $entry): string
    {
        $kind = $entry->KIND;

        if ($kind === null) {
            $kinds = $entry->select('X-ADDRESSBOOKSERVER-KIND');
            if (! empty($kinds)) {
                $kind = $kinds[0];
            }
        }

        return optional($kind)->getValue() ?? 'individual';
    }
}
