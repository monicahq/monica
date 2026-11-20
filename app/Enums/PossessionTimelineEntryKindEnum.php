<?php

declare(strict_types=1);

namespace App\Enums;

enum PossessionTimelineEntryKindEnum: string
{
    case Transaction = 'transaction';
    case Insurance = 'insurance';
    case Valuation = 'valuation';
    case Provenance = 'provenance';
    case Maintenance = 'maintenance';
    case LoanStarted = 'loan_started';
    case LoanReturned = 'loan_returned';
    case Stay = 'stay';
}
