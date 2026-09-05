<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\EmailType;

interface HasEmailType
{
    public function emailType(): EmailType;
}
