<?php

namespace App\Domains\Contact\Dav;

use App\Domains\Contact\Dav\Services\ImportVCard;
use App\Models\Account;

abstract class Importer implements ImportVCardResource
{
    public ImportVCard $context;

    /**
     * Set context.
     *
     * @param  ImportVCard  $context
     * @return ImportVCardResource
     */
    public function setContext(ImportVCard $context): ImportVCardResource
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Get the account.
     *
     * @return Account
     */
    protected function account(): Account
    {
        return $this->context->vault->account;
    }

    /**
     * Formats and returns a string for DAV Card/Cal.
     *
     * @param  null|string  $value
     * @return null|string
     */
    protected function formatValue(?string $value): ?string
    {
        return ! empty($value) ? str_replace('\;', ';', trim($value)) : null;
    }
}
