<?php

declare(strict_types=1);

namespace App\Enums;

enum AccountImportOutcomeEnum: string
{
    case Imported = 'imported';
    case MergedIntoMe = 'merged_into_me';
    case LeftOut = 'left_out';
}
