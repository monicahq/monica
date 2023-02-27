<?php

namespace App\Domains\Contact\Dav;

use App\Domains\Contact\Dav\Services\ImportVCard;
use App\Models\Account;

abstract class Importer implements ImportVCardResource
{
    public ImportVCard $context;

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
        return $this->context->vault->account;
    }

    /**
     * Formats and returns a string for DAV Card/Cal.
     */
    protected function formatValue(?string $value): ?string
    {
        return ! empty($value) ? str_replace('\;', ';', trim($value)) : null;
    }
}
