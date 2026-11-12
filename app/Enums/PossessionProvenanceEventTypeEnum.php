<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum PossessionProvenanceEventTypeEnum: string implements Translatable
{
    case Origin = 'origin';
    case Acquisition = 'acquisition';
    case Inheritance = 'inheritance';
    case Gift = 'gift';
    case Sale = 'sale';
    case Custody = 'custody';
    case Loss = 'loss';
    case Recovery = 'recovery';
    case Authentication = 'authentication';
    case Exhibition = 'exhibition';
    case Publication = 'publication';
    case Other = 'other';

    public const string GROUP_OWNERSHIP = 'ownership';

    public const string GROUP_CUSTODY = 'custody';

    public const string GROUP_AUTHENTICITY = 'authenticity';

    public function label(): string
    {
        return match ($this) {
            self::Origin => 'Origin',
            self::Acquisition => 'Acquisition',
            self::Inheritance => 'Inheritance',
            self::Gift => 'Gift',
            self::Sale => 'Sale',
            self::Custody => 'Change of custody',
            self::Loss => 'Lost or stolen',
            self::Recovery => 'Recovered',
            self::Authentication => 'Authentication',
            self::Exhibition => 'Exhibition',
            self::Publication => 'Publication',
            self::Other => 'Other',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Origin => 'star',
            self::Acquisition, self::Sale => 'coin',
            self::Inheritance => 'family',
            self::Gift => 'gift',
            self::Custody => 'place',
            self::Loss => 'info',
            self::Recovery => 'check-circle',
            self::Authentication => 'check',
            self::Exhibition => 'layers',
            self::Publication => 'file',
            self::Other => 'note',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::Origin, self::Acquisition, self::Inheritance, self::Gift, self::Sale => self::GROUP_OWNERSHIP,
            self::Custody, self::Loss, self::Recovery => self::GROUP_CUSTODY,
            self::Authentication, self::Exhibition, self::Publication, self::Other => self::GROUP_AUTHENTICITY,
        };
    }

    /**
     * What the title field suggests for an event of this type.
     */
    public function titlePlaceholder(): string
    {
        return match ($this) {
            self::Origin => 'Made by hand in Scranton',
            self::Acquisition => 'Bought new at a jeweller',
            self::Inheritance => 'Left to a grandson in a will',
            self::Gift => 'Given for a wedding',
            self::Sale => 'Sold at auction',
            self::Custody => 'Left with a jeweller for appraisal',
            self::Loss => 'Stolen during a burglary',
            self::Recovery => 'Returned by the police',
            self::Authentication => 'Certificate of authenticity issued',
            self::Exhibition => 'Shown at a fair',
            self::Publication => 'Pictured in a catalogue',
            self::Other => 'What happened',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label(), $this->titlePlaceholder()];
    }
}
