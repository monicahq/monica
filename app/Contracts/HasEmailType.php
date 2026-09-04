<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\EmailType;

/**
 * A notification that belongs in the account's email journal, and which kind of
 * email it is once it is there.
 */
interface HasEmailType
{
    public function emailType(): EmailType;
}
