<?php

declare(strict_types=1);

namespace App\Enums;

enum AccessEndReasonEnum: string
{
    case Refunded = 'refunded';
    case Disputed = 'disputed';
    case Cancelled = 'cancelled';
    case PaymentFailed = 'payment_failed';
}
