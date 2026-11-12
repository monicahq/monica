<?php

declare(strict_types=1);

namespace App\Enums;

enum SupportMessageKindEnum: string
{
    case Message = 'message';
    case Reply = 'reply';
    case Note = 'note';
}
