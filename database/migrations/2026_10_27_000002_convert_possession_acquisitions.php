<?php

declare(strict_types=1);

use App\Enums\PossessionTransactionTypeEnum;
use App\Models\Contact;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * How a possession was acquired moves from three columns of the possession to one transaction, which
 * is where it is read from from now on (specs/099). A possession recorded as bought, a gift, inherited
 * or made gets a purchase, a gift received, an inheritance or an "other" transaction, on the date it
 * carried or undated, naming the contact it came from as text. No figure is known, so none is set.
 * A possession that already holds a transaction is left alone, so the migration can run twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('possessions', 'acquisition')) {
            return;
        }

        DB::table('possessions')
            ->whereNotNull('acquisition')
            ->orderBy('id')
            ->each(function (object $possession): void {
                $alreadyConverted = DB::table('possession_transactions')
                    ->where('possession_id', $possession->id)
                    ->exists();

                if ($alreadyConverted) {
                    return;
                }

                $type = match ($possession->acquisition) {
                    'bought' => PossessionTransactionTypeEnum::Purchase,
                    'gift' => PossessionTransactionTypeEnum::GiftReceived,
                    'inherited' => PossessionTransactionTypeEnum::Inheritance,
                    default => PossessionTransactionTypeEnum::Other,
                };

                $contact = $possession->acquired_from_contact_id === null
                    ? null
                    : Contact::withTrashed()->find($possession->acquired_from_contact_id);

                DB::table('possession_transactions')->insert([
                    'uuid' => (string) Str::uuid(),
                    'account_id' => $possession->account_id,
                    'vault_id' => $possession->vault_id,
                    'possession_id' => $possession->id,
                    'type' => $type->value,
                    'occurred_on' => $possession->acquired_on,
                    'counterparty' => $contact?->name,
                    'created_at' => $possession->created_at,
                    'updated_at' => $possession->created_at,
                ]);
            });
    }
};
