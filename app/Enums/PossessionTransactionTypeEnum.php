<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum PossessionTransactionTypeEnum: string implements Translatable
{
    case Purchase = 'purchase';
    case Sale = 'sale';
    case Exchange = 'exchange';
    case GiftReceived = 'gift_received';
    case GiftGiven = 'gift_given';
    case Inheritance = 'inheritance';
    case Refund = 'refund';
    case Fee = 'fee';
    case Tax = 'tax';
    case Shipping = 'shipping';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Purchase',
            self::Sale => 'Sale',
            self::Exchange => 'Exchange',
            self::GiftReceived => 'Gift received',
            self::GiftGiven => 'Gift given',
            self::Inheritance => 'Inheritance',
            self::Refund => 'Refund',
            self::Fee => 'Fee',
            self::Tax => 'Tax',
            self::Shipping => 'Shipping',
            self::Other => 'Other',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Purchase, self::Sale => 'coin',
            self::Exchange, self::Shipping => 'layers',
            self::GiftReceived, self::GiftGiven => 'gift',
            self::Inheritance => 'family',
            self::Refund => 'arrow-right',
            self::Fee, self::Tax => 'file',
            self::Other => 'note',
        };
    }

    public function changesOwnership(): bool
    {
        return match ($this) {
            self::Purchase, self::Sale, self::Exchange, self::GiftReceived, self::GiftGiven, self::Inheritance => true,
            default => false,
        };
    }

    public function isAcquisition(): bool
    {
        return match ($this) {
            self::Purchase, self::Exchange, self::GiftReceived, self::Inheritance => true,
            default => false,
        };
    }

    public function counterpartyLabel(): string
    {
        return match ($this) {
            self::Purchase => 'Seller',
            self::Sale => 'Buyer',
            self::Exchange => 'Exchanged with',
            self::GiftReceived => 'Given by',
            self::GiftGiven => 'Given to',
            self::Inheritance => 'Inherited from',
            default => 'Counterparty',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label(), $this->counterpartyLabel()];
    }
}
