<?php

declare(strict_types=1);

namespace App\Enums;

enum PlanEnum: string
{
    case Free = 'free';
    case Starter = 'starter';
    case Business = 'business';
    case Enterprise = 'enterprise';
}
