<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DefaultLocationEnum;
use App\Enums\PossessionConditionEnum;
use App\Enums\PossessionInsuranceStatusEnum;
use App\Enums\PossessionMaintenanceTypeEnum;
use App\Enums\PossessionProvenanceDatePrecisionEnum;
use App\Enums\PossessionProvenanceEventTypeEnum;
use App\Enums\PossessionStatusEnum;
use App\Enums\PossessionTypeEnum;
use App\Enums\PossessionValuationConfidenceEnum;
use App\Enums\PossessionValuationTypeEnum;

/**
 * The possessions of the example account: what Monica keeps in the apartment, and a few things that
 * left it. Each one names a default type and a default location, and its fields by their English
 * names, so the list, the filters and the page of a possession all have something to show,
 * insurance, maintenance, valuations and provenance included.
 */
class ExamplePossessions
{
    /** @return list<array{name: string, description: ?string, type: PossessionTypeEnum, location: ?DefaultLocationEnum, status: PossessionStatusEnum, quantity: int, featured: bool, declared_value: ?string, tags: list<string>, values: array<string, mixed>, purchase: ?array{date: string, counterparty: string, amount: string}, insurance: ?array{insurer: string, status: PossessionInsuranceStatusEnum, insured_value: string, policy_number: string, coverage: string, excess: ?string, starts_on: string, ends_on: ?string, listed_individually: bool}, maintenance: list<array{type: PossessionMaintenanceTypeEnum, title: string, performed_on: string, performed_by: ?string, cost: ?string, condition_before: ?PossessionConditionEnum, condition_after: ?PossessionConditionEnum, next_due_on: ?string, description: ?string}>, valuations: list<array{type: PossessionValuationTypeEnum, valued_on: string, amount: string, confidence: PossessionValuationConfidenceEnum, valuer: ?string, method: ?string, reference_number: ?string, source_url: ?string, note: ?string}>, provenance?: list<array{type: PossessionProvenanceEventTypeEnum, title: string, date_precision: PossessionProvenanceDatePrecisionEnum, occurred_on: ?string, from: ?string, to: ?string, location: ?string, description: ?string, verified: bool, linked_to_purchase: bool}>, loans?: list<array{contact: string, started_on: string, due_on: ?string, returned_on: string, condition_out: ?PossessionConditionEnum, condition_back: ?PossessionConditionEnum, purpose: ?string}>}> */
    public function all(): array
    {
        return [
            [
                'name' => 'Yellow picture frame',
                'description' => 'The frame around the peephole of the front door. It came with the apartment.',
                'type' => PossessionTypeEnum::Art,
                'location' => DefaultLocationEnum::House,
                'status' => PossessionStatusEnum::Owned,
                'quantity' => 1,
                'featured' => true,
                'declared_value' => '40',
                'tags' => ['Apartment 20'],
                'values' => [
                    'Category' => 'Decorative object',
                    'Materials' => 'Painted wood',
                    'Dimensions' => '30 × 25 cm',
                ],
                'purchase' => null,
                'insurance' => null,
                'maintenance' => [
                    ['type' => PossessionMaintenanceTypeEnum::Restoration, 'title' => 'Repainted the frame', 'performed_on' => '2001-04-02', 'performed_by' => null, 'cost' => null, 'condition_before' => PossessionConditionEnum::Damaged, 'condition_after' => PossessionConditionEnum::Used, 'next_due_on' => null, 'description' => 'Chipped when the door was slammed one time too many.'],
                ],
                'valuations' => [
                    ['type' => PossessionValuationTypeEnum::OwnEstimate, 'valued_on' => '2024-09-01', 'amount' => '40.00', 'confidence' => PossessionValuationConfidenceEnum::Uncertain, 'valuer' => 'Monica Geller', 'method' => null, 'reference_number' => null, 'source_url' => null, 'note' => 'Worth nothing to anybody else, and everything to whoever lives here.'],
                ],
            ],
            [
                'name' => 'Engagement ring',
                'description' => 'Chandler hid it in the champagne glass first, then thought better of it.',
                'type' => PossessionTypeEnum::Jewellery,
                'location' => DefaultLocationEnum::Bedroom,
                'status' => PossessionStatusEnum::Owned,
                'quantity' => 1,
                'featured' => true,
                'declared_value' => '4800',
                'tags' => ['Insured', 'Sentimental'],
                'values' => [
                    'Category' => 'Ring',
                    'Metal' => 'White gold',
                    'Stones' => 'Diamond',
                    'Weight in g' => '3.2',
                ],
                'purchase' => ['date' => '2000-05-18', 'counterparty' => 'Tiffany', 'amount' => '4800.00'],
                'insurance' => ['insurer' => 'Chubb', 'status' => PossessionInsuranceStatusEnum::Active, 'insured_value' => '5500.00', 'policy_number' => 'CH-2000-48213', 'coverage' => 'Loss, theft and accidental damage, at home and abroad.', 'excess' => '250.00', 'starts_on' => '2000-06-01', 'ends_on' => null, 'listed_individually' => true],
                'maintenance' => [
                    ['type' => PossessionMaintenanceTypeEnum::Cleaning, 'title' => 'Cleaning and prong check', 'performed_on' => '2025-12-15', 'performed_by' => 'Tiffany', 'cost' => '0.00', 'condition_before' => PossessionConditionEnum::Used, 'condition_after' => PossessionConditionEnum::LikeNew, 'next_due_on' => '2026-12-15', 'description' => null],
                ],
                'valuations' => [
                    ['type' => PossessionValuationTypeEnum::Appraisal, 'valued_on' => '2000-05-20', 'amount' => '4500.00', 'confidence' => PossessionValuationConfidenceEnum::Certain, 'valuer' => 'Tiffany', 'method' => 'Written appraisal at purchase', 'reference_number' => 'TIF-2000-0517', 'source_url' => null, 'note' => null],
                    ['type' => PossessionValuationTypeEnum::InsuranceDeclaration, 'valued_on' => '2000-06-01', 'amount' => '5500.00', 'confidence' => PossessionValuationConfidenceEnum::Probable, 'valuer' => 'Chubb', 'method' => 'Declared value', 'reference_number' => 'CH-2000-48213', 'source_url' => null, 'note' => 'Declared when the ring was added to the policy.'],
                    ['type' => PossessionValuationTypeEnum::Appraisal, 'valued_on' => '2025-12-15', 'amount' => '6200.00', 'confidence' => PossessionValuationConfidenceEnum::Certain, 'valuer' => 'Diamond District Appraisers', 'method' => 'Inspection and comparable sales', 'reference_number' => 'DDA-2025-1215', 'source_url' => 'https://diamond-district-appraisers.example/appraisals/1215', 'note' => 'The setting is now worth more than the stone was.'],
                ],
                'provenance' => [
                    ['type' => PossessionProvenanceEventTypeEnum::Origin, 'title' => 'Set by hand in New York', 'date_precision' => PossessionProvenanceDatePrecisionEnum::Circa, 'occurred_on' => '2000', 'from' => null, 'to' => null, 'location' => 'New York', 'description' => 'The jeweller remembered the setting but kept no record of the day.', 'verified' => false, 'linked_to_purchase' => false],
                    ['type' => PossessionProvenanceEventTypeEnum::Acquisition, 'title' => 'Bought by Chandler at Tiffany', 'date_precision' => PossessionProvenanceDatePrecisionEnum::Day, 'occurred_on' => '2000-05-18', 'from' => 'Tiffany', 'to' => 'Chandler Bing', 'location' => 'New York', 'description' => 'Chosen after Phoebe talked him out of the one he could not afford.', 'verified' => true, 'linked_to_purchase' => true],
                    ['type' => PossessionProvenanceEventTypeEnum::Gift, 'title' => 'Given to Monica when Chandler proposed', 'date_precision' => PossessionProvenanceDatePrecisionEnum::Month, 'occurred_on' => '2000-05', 'from' => 'Chandler Bing', 'to' => 'Monica Geller', 'location' => 'Apartment 20', 'description' => null, 'verified' => false, 'linked_to_purchase' => false],
                ],
                'loans' => [
                    ['contact' => 'Rachel Green', 'started_on' => '2003-06-14', 'due_on' => '2003-06-16', 'returned_on' => '2003-06-16', 'condition_out' => PossessionConditionEnum::LikeNew, 'condition_back' => PossessionConditionEnum::LikeNew, 'purpose' => 'Borrowed for a Ralph Lauren photo shoot, with a promise to guard it with her life.'],
                ],
            ],
            [
                'name' => 'Dishwasher',
                'description' => null,
                'type' => PossessionTypeEnum::HouseholdAppliances,
                'location' => DefaultLocationEnum::Kitchen,
                'status' => PossessionStatusEnum::Owned,
                'quantity' => 1,
                'featured' => false,
                'declared_value' => '650',
                'tags' => ['Kitchen'],
                'values' => [
                    'Category' => 'Dishwasher',
                    'Brand' => 'Bosch',
                    'Model' => 'Serie 4',
                    'Installation date' => '2001-03-12',
                ],
                'purchase' => ['date' => '2001-03-02', 'counterparty' => 'P.C. Richard and Son', 'amount' => '649.99'],
                'insurance' => ['insurer' => 'P.C. Richard and Son', 'status' => PossessionInsuranceStatusEnum::Expired, 'insured_value' => '649.99', 'policy_number' => 'EXT-4417', 'coverage' => 'Extended warranty on parts and labour.', 'excess' => null, 'starts_on' => '2001-03-02', 'ends_on' => '2004-03-02', 'listed_individually' => true],
                'maintenance' => [
                    ['type' => PossessionMaintenanceTypeEnum::Servicing, 'title' => 'Annual service', 'performed_on' => '2002-03-10', 'performed_by' => 'Bosch service', 'cost' => '89.00', 'condition_before' => PossessionConditionEnum::Used, 'condition_after' => PossessionConditionEnum::Used, 'next_due_on' => '2003-03-10', 'description' => null],
                    ['type' => PossessionMaintenanceTypeEnum::Repair, 'title' => 'Replaced the drain pump', 'performed_on' => '2003-08-21', 'performed_by' => 'Bosch service', 'cost' => '145.00', 'condition_before' => PossessionConditionEnum::Broken, 'condition_after' => PossessionConditionEnum::Used, 'next_due_on' => null, 'description' => 'It flooded the kitchen the night of the Thanksgiving dinner.'],
                ],
                'valuations' => [],
            ],
            [
                'name' => 'Chef’s knife set',
                'description' => 'Eleven knives in a block. Nobody else is allowed to put them in the dishwasher.',
                'type' => PossessionTypeEnum::Tools,
                'location' => DefaultLocationEnum::Kitchen,
                'status' => PossessionStatusEnum::Owned,
                'quantity' => 11,
                'featured' => false,
                'declared_value' => '520',
                'tags' => ['Kitchen', 'Work'],
                'values' => [
                    'Category' => 'Kitchen knives',
                    'Brand' => 'Wüsthof',
                    'Power source' => 'Manual',
                ],
                'purchase' => null,
                'insurance' => null,
                'maintenance' => [
                    ['type' => PossessionMaintenanceTypeEnum::Servicing, 'title' => 'Professional sharpening', 'performed_on' => '2026-05-04', 'performed_by' => 'Bowery Kitchen Supplies', 'cost' => '60.00', 'condition_before' => PossessionConditionEnum::Worn, 'condition_after' => PossessionConditionEnum::LikeNew, 'next_due_on' => '2026-11-04', 'description' => null],
                ],
                'valuations' => [
                    ['type' => PossessionValuationTypeEnum::ComparableListing, 'valued_on' => '2026-05-04', 'amount' => '480.00', 'confidence' => PossessionValuationConfidenceEnum::Probable, 'valuer' => null, 'method' => 'Same set, second hand', 'reference_number' => null, 'source_url' => 'https://kitchen-resale.example/listings/8812', 'note' => null],
                ],
            ],
            [
                'name' => 'Be Your Own Windkeeper',
                'description' => 'Rachel borrowed it and has not given it back.',
                'type' => PossessionTypeEnum::Books,
                'location' => null,
                'status' => PossessionStatusEnum::Owned,
                'quantity' => 1,
                'featured' => false,
                'declared_value' => null,
                'tags' => [],
                'values' => [
                    'My rating' => 2,
                    'Author' => 'Unknown',
                    'First edition' => false,
                ],
                'purchase' => null,
                'insurance' => null,
                'maintenance' => [],
                'valuations' => [],
            ],
            [
                'name' => 'Holiday Armadillo costume',
                'description' => 'Worn once, at Hanukkah.',
                'type' => PossessionTypeEnum::Clothing,
                'location' => DefaultLocationEnum::StorageSpace,
                'status' => PossessionStatusEnum::Owned,
                'quantity' => 1,
                'featured' => false,
                'declared_value' => '75',
                'tags' => ['Holidays'],
                'values' => [
                    'Category' => 'Costume',
                    'Size' => 'Adult',
                    'Colour' => 'Grey',
                ],
                'purchase' => null,
                'insurance' => null,
                'maintenance' => [],
                'valuations' => [],
            ],
            [
                'name' => 'Bottle of Château Margaux',
                'description' => 'Kept for an anniversary.',
                'type' => PossessionTypeEnum::Wine,
                'location' => DefaultLocationEnum::Kitchen,
                'status' => PossessionStatusEnum::Owned,
                'quantity' => 2,
                'featured' => false,
                'declared_value' => '900',
                'tags' => ['Sentimental'],
                'values' => [
                    'Bottle size' => 'Standard/750ml',
                    'Producer/winery' => 'Château Margaux',
                    'Vintage' => 1994,
                    'Region' => 'Bordeaux',
                ],
                'purchase' => ['date' => '1999-11-20', 'counterparty' => 'Sherry-Lehmann', 'amount' => '900.00'],
                'insurance' => ['insurer' => 'State Farm', 'status' => PossessionInsuranceStatusEnum::Active, 'insured_value' => '1000.00', 'policy_number' => 'SF-77-310', 'coverage' => 'Household contents, breakage included.', 'excess' => '100.00', 'starts_on' => '2003-01-01', 'ends_on' => '2026-12-31', 'listed_individually' => false],
                'maintenance' => [],
                'valuations' => [
                    ['type' => PossessionValuationTypeEnum::AuctionResult, 'valued_on' => '2019-11-14', 'amount' => '750.00', 'confidence' => PossessionValuationConfidenceEnum::Probable, 'valuer' => 'Manhattan Wine Auctions', 'method' => 'Same vintage, sold at auction', 'reference_number' => 'LOT-214', 'source_url' => null, 'note' => null],
                    ['type' => PossessionValuationTypeEnum::OfferReceived, 'valued_on' => '2026-02-02', 'amount' => '1150.00', 'confidence' => PossessionValuationConfidenceEnum::Certain, 'valuer' => 'Gunther', 'method' => null, 'reference_number' => null, 'source_url' => null, 'note' => 'Offered for the Central Perk anniversary. Monica said no.'],
                ],
            ],
            [
                'name' => 'Ms. Pac-Man machine',
                'description' => 'Bought for Joey and Chandler, then nobody could get the game to stop.',
                'type' => PossessionTypeEnum::VideoGames,
                'location' => null,
                'status' => PossessionStatusEnum::GivenAway,
                'quantity' => 1,
                'featured' => false,
                'declared_value' => null,
                'tags' => [],
                'values' => [
                    'My rating' => 5,
                    'Platform' => 'Other',
                    'Release year' => 1982,
                ],
                'purchase' => null,
                'insurance' => null,
                'maintenance' => [],
                'valuations' => [],
            ],
            [
                'name' => 'Laptop',
                'description' => null,
                'type' => PossessionTypeEnum::Electronics,
                'location' => null,
                'status' => PossessionStatusEnum::Sold,
                'quantity' => 1,
                'featured' => false,
                'declared_value' => null,
                'tags' => ['Work'],
                'values' => [
                    'Category' => 'Computer',
                    'Brand' => 'Apple',
                    'Model' => 'PowerBook G3',
                    'Year' => 1999,
                    'Memory in GB' => '0.5',
                ],
                'purchase' => ['date' => '1999-06-01', 'counterparty' => 'CompUSA', 'amount' => '2499.00'],
                'insurance' => ['insurer' => 'State Farm', 'status' => PossessionInsuranceStatusEnum::Cancelled, 'insured_value' => '2000.00', 'policy_number' => 'SF-99-118', 'coverage' => 'Accidental damage, cancelled when the laptop was sold.', 'excess' => '150.00', 'starts_on' => '1999-06-01', 'ends_on' => '2002-09-15', 'listed_individually' => false],
                'maintenance' => [],
                'valuations' => [],
            ],
            [
                'name' => 'Foosball table',
                'description' => 'Moved across the hall when the boys swapped apartments.',
                'type' => PossessionTypeEnum::Sports,
                'location' => DefaultLocationEnum::LivingRoom,
                'status' => PossessionStatusEnum::Owned,
                'quantity' => 1,
                'featured' => false,
                'declared_value' => '300',
                'tags' => ['Apartment 20'],
                'values' => [
                    'Activity' => 'Table football',
                    'Weight in kg' => 70,
                ],
                'purchase' => null,
                'insurance' => null,
                'maintenance' => [
                    ['type' => PossessionMaintenanceTypeEnum::PartReplacement, 'title' => 'Replaced the goalkeeper rod', 'performed_on' => '1998-10-11', 'performed_by' => 'Chandler Bing', 'cost' => '35.00', 'condition_before' => PossessionConditionEnum::Damaged, 'condition_after' => PossessionConditionEnum::Used, 'next_due_on' => null, 'description' => null],
                ],
                'valuations' => [
                    ['type' => PossessionValuationTypeEnum::OwnEstimate, 'valued_on' => '2001-01-10', 'amount' => '300.00', 'confidence' => PossessionValuationConfidenceEnum::Uncertain, 'valuer' => 'Chandler Bing', 'method' => null, 'reference_number' => null, 'source_url' => null, 'note' => 'What Chandler and Joey would have asked for it, had they ever let it go.'],
                ],
            ],
        ];
    }
}
