<?php

declare(strict_types=1);

namespace App\Contracts;

use BackedEnum;

interface SuppliedValueListItem extends BackedEnum, Translatable
{
    public function label(): string;
}
