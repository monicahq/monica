<?php

namespace App\Enums;

enum ImportJobStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case CANCELLING = 'cancelling';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}
