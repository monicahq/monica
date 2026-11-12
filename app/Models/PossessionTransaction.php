<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionTransactionTypeEnum;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInVault;
use Carbon\CarbonImmutable;
use Database\Factories\PossessionTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Override;

/**
 * Class PossessionTransaction
 *
 * Money or ownership changing hands for one possession: a purchase, a sale, an
 * inheritance, a fee, the shipping. The possession's acquisition date, price
 * and the costs since are read from its transactions and never copied onto it,
 * so editing a transaction changes them.
 *
 * Every figure is optional and never below zero, and all of them are in the
 * one currency of the transaction, which is required once a figure is set.
 * Nothing is converted. The total is the one typed in, else the sum of the
 * figures set, else unknown. The date may be unknown too, since an inheritance
 * remembered decades later rarely comes with one.
 *
 * It carries the account and the vault of its possession so that an address
 * naming it is scoped like every other record of a vault.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $vault_id
 * @property int $possession_id
 * @property PossessionTransactionTypeEnum $type
 * @property CarbonImmutable|null $occurred_on
 * @property string|null $counterparty
 * @property string|null $amount
 * @property string|null $tax
 * @property string|null $fees
 * @property string|null $shipping
 * @property string|null $entered_total
 * @property CurrencyEnum|null $currency
 * @property string|null $reference
 * @property string|null $source_url
 * @property string|null $note
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Possession $possession
 * @property-read Collection<int, File> $documents
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'vault_id',
    'possession_id',
    'type',
    'occurred_on',
    'counterparty',
    'amount',
    'tax',
    'fees',
    'shipping',
    'entered_total',
    'currency',
    'reference',
    'source_url',
    'note',
])]
class PossessionTransaction extends Model
{
    /** @use HasFactory<PossessionTransactionFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInVault;

    /** @var list<string> */
    public const array FIGURES = ['amount', 'tax', 'fees', 'shipping'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'type' => PossessionTransactionTypeEnum::class,
            'occurred_on' => 'immutable_date',
            'amount' => 'decimal:2',
            'tax' => 'decimal:2',
            'fees' => 'decimal:2',
            'shipping' => 'decimal:2',
            'entered_total' => 'decimal:2',
            'currency' => CurrencyEnum::class,
        ];
    }

    /**
     * Get the possession the transaction belongs to.
     *
     * @return BelongsTo<Possession, $this>
     */
    public function possession(): BelongsTo
    {
        return $this->belongsTo(Possession::class);
    }

    /**
     * Get the invoices, receipts and other files attached to the transaction.
     *
     * @return BelongsToMany<File, $this>
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(File::class, 'possession_transaction_file')->withTimestamps();
    }

    /**
     * The total of the transaction: the one typed in, else the sum of the
     * figures set, else null when nothing was set.
     */
    public function total(): ?string
    {
        if ($this->entered_total !== null) {
            return $this->entered_total;
        }

        $figures = $this->figuresSet();

        if ($figures === []) {
            return null;
        }

        return array_reduce($figures, fn (string $sum, string $figure): string => bcadd($sum, $figure, 2), '0.00');
    }

    /**
     * Where the total comes from: entered, sum or unknown.
     */
    public function totalSource(): string
    {
        if ($this->entered_total !== null) {
            return 'entered';
        }

        return $this->figuresSet() === [] ? 'unknown' : 'sum';
    }

    /**
     * The figures set, keyed by their column, in the order of the form.
     *
     * @return array<string, string>
     */
    public function figuresSet(): array
    {
        $figures = [];

        foreach (self::FIGURES as $column) {
            if ($this->{$column} !== null) {
                $figures[$column] = $this->{$column};
            }
        }

        return $figures;
    }
}
