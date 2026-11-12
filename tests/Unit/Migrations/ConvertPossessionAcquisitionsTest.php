<?php

declare(strict_types=1);

namespace Tests\Unit\Migrations;

use App\Enums\PossessionTransactionTypeEnum;
use App\Models\Contact;
use App\Models\Possession;
use App\Models\PossessionTransaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConvertPossessionAcquisitionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_possession_bought_on_a_date_becomes_a_purchase_on_that_date(): void
    {
        $this->bringBackTheColumns();
        $sebring = Possession::factory()->create(['name' => 'Chrysler Sebring']);
        $this->recordAcquisition($sebring, 'bought', '2005-03-24', null);

        $this->migration()->up();

        $transaction = PossessionTransaction::query()->where('possession_id', $sebring->id)->sole();
        $this->assertSame(PossessionTransactionTypeEnum::Purchase, $transaction->type);
        $this->assertSame('2005-03-24', $transaction->occurred_on?->toDateString());
        $this->assertNull($transaction->counterparty);
        $this->assertNull($transaction->currency);
        $this->assertSame($sebring->vault_id, $transaction->vault_id);
    }

    #[Test]
    public function a_gift_keeps_the_name_of_whoever_gave_it_even_once_deleted(): void
    {
        $this->bringBackTheColumns();
        $mug = Possession::factory()->create();
        $jim = Contact::factory()->create(['vault_id' => $mug->vault_id, 'first_name' => 'Jim', 'last_name' => 'Halpert']);
        $this->recordAcquisition($mug, 'gift', null, $jim->id);
        $jim->delete();

        $this->migration()->up();

        $transaction = PossessionTransaction::query()->where('possession_id', $mug->id)->sole();
        $this->assertSame(PossessionTransactionTypeEnum::GiftReceived, $transaction->type);
        $this->assertNull($transaction->occurred_on);
        $this->assertSame('Jim Halpert', $transaction->counterparty);
    }

    #[Test]
    public function an_inheritance_and_something_made_become_their_own_types(): void
    {
        $this->bringBackTheColumns();
        $press = Possession::factory()->create();
        $quilt = Possession::factory()->create();
        $this->recordAcquisition($press, 'inherited', '1998-07-01', null);
        $this->recordAcquisition($quilt, 'made', null, null);

        $this->migration()->up();

        $this->assertSame(PossessionTransactionTypeEnum::Inheritance, PossessionTransaction::query()->where('possession_id', $press->id)->sole()->type);
        $this->assertSame(PossessionTransactionTypeEnum::Other, PossessionTransaction::query()->where('possession_id', $quilt->id)->sole()->type);
    }

    #[Test]
    public function a_possession_without_an_acquisition_gets_nothing_and_a_second_run_adds_nothing(): void
    {
        $this->bringBackTheColumns();
        $stapler = Possession::factory()->create();
        $sebring = Possession::factory()->create();
        $this->recordAcquisition($sebring, 'bought', null, null);

        $this->migration()->up();
        $this->migration()->up();

        $this->assertSame(0, PossessionTransaction::query()->where('possession_id', $stapler->id)->count());
        $this->assertSame(1, PossessionTransaction::query()->where('possession_id', $sebring->id)->count());
    }

    #[Test]
    public function it_does_nothing_once_the_columns_are_gone(): void
    {
        Possession::factory()->create();

        $this->migration()->up();

        $this->assertSame(0, PossessionTransaction::query()->count());
    }

    private function bringBackTheColumns(): void
    {
        Schema::table('possessions', function (Blueprint $table): void {
            $table->string('acquisition')->nullable();
            $table->unsignedBigInteger('acquired_from_contact_id')->nullable();
            $table->date('acquired_on')->nullable();
        });
    }

    private function recordAcquisition(Possession $possession, string $way, ?string $on, ?int $contactId): void
    {
        DB::table('possessions')->where('id', $possession->id)->update([
            'acquisition' => $way,
            'acquired_on' => $on,
            'acquired_from_contact_id' => $contactId,
        ]);
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_10_27_000002_convert_possession_acquisitions.php');
    }
}
