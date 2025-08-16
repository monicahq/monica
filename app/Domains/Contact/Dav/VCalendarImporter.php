<?php

namespace App\Domains\Contact\Dav;

use App\Domains\Contact\Dav\Services\ImportVCalendar;
use App\Models\Account;
use App\Models\User;
use App\Models\Vault;
use Ramsey\Uuid\Uuid;
use Sabre\VObject\Component\VCalendar;

abstract class VCalendarImporter implements ImportVCalendarResource
{
    public ImportVCalendar $context;

    /**
     * Can import Card.
     */
    public function can(VCalendar $vcalendar): bool
    {
        return true;
    }

    /**
     * Set context.
     */
    public function setContext(ImportVCalendar $context): ImportVCalendarResource
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
    abstract protected function getUid(VCalendar $entry): ?string;

    /**
     * Import UID.
     */
    protected function importUid(array $data, VCalendar $entry): array
    {
        if (($uuid = $this->getUid($entry)) !== null && Uuid::isValid($uuid) && ! $this->context->external) {
            $data['id'] = $uuid;
        }

        return $data;
    }
}
