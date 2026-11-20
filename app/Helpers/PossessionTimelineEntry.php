<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\PossessionProvenanceDatePrecisionEnum;
use App\Enums\PossessionTimelineEntryKindEnum;
use App\Enums\PossessionTimelineSectionEnum;
use App\Models\PossessionInsuranceRecord;
use App\Models\PossessionLoan;
use App\Models\PossessionLocationStay;
use App\Models\PossessionMaintenanceRecord;
use App\Models\PossessionProvenanceEvent;
use App\Models\PossessionTransaction;
use App\Models\PossessionValuation;
use Carbon\CarbonImmutable;

class PossessionTimelineEntry
{
    public function __construct(
        public readonly PossessionTimelineSectionEnum $section,
        public readonly PossessionTimelineEntryKindEnum $kind,
        public readonly PossessionTransaction|PossessionInsuranceRecord|PossessionValuation|PossessionProvenanceEvent|PossessionMaintenanceRecord|PossessionLoan|PossessionLocationStay $record,
        public readonly ?CarbonImmutable $date,
        public readonly PossessionProvenanceDatePrecisionEnum $precision = PossessionProvenanceDatePrecisionEnum::Day,
        public readonly bool $isCurrent = false,
        public readonly bool $isAcquisition = false,
    ) {}
}
