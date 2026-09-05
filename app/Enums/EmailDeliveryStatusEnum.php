<?php

declare(strict_types=1);

namespace App\Enums;

enum EmailDeliveryStatusEnum: string
{
    case Bounced = 'bounced';
    case Complained = 'complained';
    case Delivered = 'delivered';
    case Sent = 'sent';
}
