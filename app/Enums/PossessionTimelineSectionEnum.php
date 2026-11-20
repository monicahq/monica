<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum PossessionTimelineSectionEnum: string implements Translatable
{
    case Transactions = 'transactions';
    case Insurance = 'insurance';
    case Valuations = 'valuations';
    case Provenance = 'provenance';
    case Maintenance = 'maintenance';
    case Loans = 'loans';
    case Locations = 'locations';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Transactions => 'Transactions',
            self::Insurance => 'Insurance',
            self::Valuations => 'Valuations',
            self::Provenance => 'Provenance',
            self::Maintenance => 'Maintenance',
            self::Loans => 'Loans',
            self::Locations => 'Locations',
        };
    }

    public function noun(): string
    {
        return match ($this) {
            self::Transactions => 'Transaction',
            self::Insurance => 'Insurance',
            self::Valuations => 'Valuation',
            self::Provenance => 'Provenance',
            self::Maintenance => 'Maintenance',
            self::Loans => 'Loan',
            self::Locations => 'Location',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Transactions => 'coin',
            self::Insurance => 'lock',
            self::Valuations => 'activity',
            self::Provenance => 'family',
            self::Maintenance => 'settings',
            self::Loans => 'relationship',
            self::Locations => 'place',
        };
    }

    public function colour(): VaultColourEnum
    {
        return match ($this) {
            self::Transactions => VaultColourEnum::Green,
            self::Insurance => VaultColourEnum::Rust,
            self::Valuations => VaultColourEnum::Blue,
            self::Provenance => VaultColourEnum::Violet,
            self::Maintenance => VaultColourEnum::Amber,
            self::Loans => VaultColourEnum::Plum,
            self::Locations => VaultColourEnum::Teal,
        };
    }

    public function routeName(): string
    {
        return match ($this) {
            self::Transactions => 'possessions.transactions.index',
            self::Insurance => 'possessions.insuranceRecords.index',
            self::Valuations => 'possessions.valuations.index',
            self::Provenance => 'possessions.provenance.index',
            self::Maintenance => 'possessions.maintenanceRecords.index',
            self::Loans => 'possessions.loans.index',
            self::Locations => 'possessions.locationStays.index',
        };
    }

    /** The prefix of the anchor the section page gives each of its records, or null when it gives none. */
    public function anchorPrefix(): ?string
    {
        return match ($this) {
            self::Transactions => 'transaction',
            self::Insurance => 'insurance',
            self::Valuations => 'valuation',
            self::Provenance => 'provenance',
            self::Loans => 'loan',
            self::Maintenance, self::Locations => null,
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label(), $this->noun()];
    }
}
