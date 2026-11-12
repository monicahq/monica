<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum PossessionValuationTypeEnum: string implements Translatable
{
    case OwnEstimate = 'own_estimate';
    case Appraisal = 'appraisal';
    case InsuranceDeclaration = 'insurance_declaration';
    case AuctionResult = 'auction_result';
    case ComparableListing = 'comparable_listing';
    case OfferReceived = 'offer_received';

    public function label(): string
    {
        return match ($this) {
            self::OwnEstimate => 'Own estimate',
            self::Appraisal => 'Appraisal',
            self::InsuranceDeclaration => 'Insurance declaration',
            self::AuctionResult => 'Auction result',
            self::ComparableListing => 'Comparable listing',
            self::OfferReceived => 'Offer received',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::OwnEstimate => 'note',
            self::Appraisal => 'activity',
            self::InsuranceDeclaration => 'lock',
            self::AuctionResult => 'coin',
            self::ComparableListing => 'link',
            self::OfferReceived => 'relationship',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
