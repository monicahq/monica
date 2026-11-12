<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\PossessionProvenanceDatePrecisionEnum;
use App\Enums\PossessionProvenanceEventTypeEnum;
use App\Models\Possession;
use App\Models\PossessionProvenanceEvent;
use App\Models\PossessionTransaction;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\PossessionProvenanceViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionProvenanceViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_summarises_a_possession_without_provenance(): void
    {
        [$user, $press] = $this->press();

        $viewModel = new PossessionProvenanceViewModel(user: $user, possession: $press);

        $this->assertSame([
            'origin' => 'Unknown',
            'originNote' => 'No origin event yet',
            'chain' => 'No owner recorded',
            'chainNote' => 'Add an acquisition, a gift or an inheritance',
            'verified' => '—',
            'verifiedNote' => 'Every event is backed by a record',
        ], $viewModel->summary());
        $this->assertSame([], $viewModel->events());
        $this->assertSame(['all' => 0, 'ownership' => 0, 'custody' => 0, 'authenticity' => 0], $viewModel->counts());
    }

    #[Test]
    public function it_lists_the_events_oldest_first_with_an_unknown_date_last_and_ties_in_recording_order(): void
    {
        [$user, $press] = $this->press();
        $this->event($press, ['title' => 'Unknown day', 'date_precision' => PossessionProvenanceDatePrecisionEnum::Unknown, 'occurred_on' => null]);
        $this->event($press, ['title' => 'Second in 2005', 'occurred_on' => '2005-03-24']);
        $this->event($press, ['title' => 'Built', 'date_precision' => PossessionProvenanceDatePrecisionEnum::Circa, 'occurred_on' => '1921-01-01']);
        $this->event($press, ['title' => 'Third in 2005', 'occurred_on' => '2005-03-24']);

        $titles = array_column(new PossessionProvenanceViewModel(user: $user, possession: $press)->events(), 'title');

        $this->assertSame(['Built', 'Second in 2005', 'Third in 2005', 'Unknown day'], $titles);
    }

    #[Test]
    public function it_writes_each_date_with_its_precision_and_falls_back_to_the_type_for_the_title(): void
    {
        [$user, $press] = $this->press();
        $this->event($press, ['date_precision' => PossessionProvenanceDatePrecisionEnum::Month, 'occurred_on' => '2005-03-01']);
        $this->event($press, ['date_precision' => PossessionProvenanceDatePrecisionEnum::Year, 'occurred_on' => '2006-01-01']);
        $this->event($press, ['date_precision' => PossessionProvenanceDatePrecisionEnum::Circa, 'occurred_on' => '2007-01-01']);
        $this->event($press, ['date_precision' => PossessionProvenanceDatePrecisionEnum::Unknown, 'occurred_on' => null, 'type' => PossessionProvenanceEventTypeEnum::Loss]);

        $events = new PossessionProvenanceViewModel(user: $user, possession: $press)->events();

        $this->assertSame(['March 2005', '2006', 'About 2007', 'Date unknown'], array_column($events, 'date'));
        $this->assertSame('Lost or stolen', $events[3]['title']);
    }

    #[Test]
    public function it_names_who_and_where_in_one_line(): void
    {
        [$user, $press] = $this->press();
        $this->event($press, ['occurred_on' => '2001-01-01', 'from' => 'Mose Schrute', 'to' => 'Dwight Schrute', 'location' => 'Honesdale']);
        $this->event($press, ['occurred_on' => '2002-01-01', 'to' => 'Angela Martin']);
        $this->event($press, ['occurred_on' => '2003-01-01']);

        $who = array_column(new PossessionProvenanceViewModel(user: $user, possession: $press)->events(), 'who');

        $this->assertSame(['From Mose Schrute to Dwight Schrute in Honesdale.', 'To Angela Martin.', null], $who);
    }

    #[Test]
    public function it_summarises_the_origin_the_owners_and_what_is_verified(): void
    {
        [$user, $press] = $this->press();
        $this->event($press, ['type' => PossessionProvenanceEventTypeEnum::Origin, 'date_precision' => PossessionProvenanceDatePrecisionEnum::Circa, 'occurred_on' => '1921-01-01', 'location' => 'Honesdale', 'is_verified' => true]);
        $this->event($press, ['type' => PossessionProvenanceEventTypeEnum::Inheritance, 'occurred_on' => '1970-05-01', 'to' => 'Mose Schrute', 'is_verified' => true]);
        $this->event($press, ['type' => PossessionProvenanceEventTypeEnum::Gift, 'occurred_on' => '1990-05-01', 'to' => 'Dwight Schrute']);
        $this->event($press, ['type' => PossessionProvenanceEventTypeEnum::Custody, 'occurred_on' => '2008-05-01', 'to' => 'Angela Martin']);
        $this->event($press, ['type' => PossessionProvenanceEventTypeEnum::Recovery, 'occurred_on' => '2009-05-01', 'to' => ' Dwight Schrute ']);
        $this->event($press, ['type' => PossessionProvenanceEventTypeEnum::Acquisition, 'occurred_on' => '2010-05-01', 'to' => 'Dwight Schrute']);

        $viewModel = new PossessionProvenanceViewModel(user: $user, possession: $press);
        $summary = $viewModel->summary();

        $this->assertSame('Honesdale', $summary['origin']);
        $this->assertSame('About 1921, verified', $summary['originNote']);
        $this->assertSame('2 owners known', $summary['chain']);
        $this->assertSame('Mose Schrute, then Dwight Schrute', $summary['chainNote']);
        $this->assertSame('2 of 6 events', $summary['verified']);
        $this->assertSame('4 events rest on memory alone', $summary['verifiedNote']);
        $this->assertSame(['all' => 6, 'ownership' => 4, 'custody' => 2, 'authenticity' => 0], $viewModel->counts());
    }

    #[Test]
    public function it_reads_what_a_linked_moment_cost_from_its_transaction(): void
    {
        [$user, $press] = $this->press();
        $purchase = PossessionTransaction::factory()->create(['possession_id' => $press->id, 'amount' => '40.00']);
        $this->event($press, ['possession_transaction_id' => $purchase->id]);
        $this->event($press, ['occurred_on' => '2006-01-01']);

        $events = new PossessionProvenanceViewModel(user: $user, possession: $press)->events();

        $this->assertStringStartsWith('Linked transaction: Purchase, ', $events[0]['transaction']['label'] ?? '');
        $this->assertSame('$40', $events[0]['transaction']['total'] ?? null);
        $this->assertStringEndsWith('The linked transaction stays in Transactions.', $events[0]['deleteWarning']);
        $this->assertNull($events[1]['transaction']);
        $this->assertSame('It is removed from the provenance of this possession, and this cannot be undone.', $events[1]['deleteWarning']);
    }

    #[Test]
    public function it_offers_the_transactions_of_the_possession_oldest_first(): void
    {
        [$user, $press] = $this->press();
        PossessionTransaction::factory()->create(['possession_id' => $press->id, 'occurred_on' => '2010-01-01', 'counterparty' => 'Schrute Farms']);
        PossessionTransaction::factory()->create(['possession_id' => $press->id, 'occurred_on' => '1990-01-01', 'counterparty' => 'Honesdale Hardware']);
        PossessionTransaction::factory()->create(['occurred_on' => '2000-01-01', 'counterparty' => 'Staples']);

        $labels = array_column(new PossessionProvenanceViewModel(user: $user, possession: $press)->transactionOptions(), 'label');

        $this->assertCount(2, $labels);
        $this->assertStringContainsString('Honesdale Hardware', $labels[0]);
        $this->assertStringContainsString('Schrute Farms', $labels[1]);
    }

    #[Test]
    public function it_fills_the_form_with_the_event_being_edited_and_leaves_it_out_of_the_chain(): void
    {
        [$user, $press] = $this->press();
        $event = $this->event($press, ['date_precision' => PossessionProvenanceDatePrecisionEnum::Month, 'occurred_on' => '2005-03-01', 'is_verified' => true]);

        $viewModel = new PossessionProvenanceViewModel(user: $user, possession: $press, editing: $event);
        $form = $viewModel->form();

        $this->assertTrue($form['isEditing']);
        $this->assertSame('2005-03', $form['values']['occurred_on']);
        $this->assertSame('month', $form['values']['date_precision']);
        $this->assertTrue($form['isVerified']);
        $this->assertSame([], $viewModel->events());
    }

    /** @return array{User, Possession} */
    private function press(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Beet press'])];
    }

    /** @param array<string, mixed> $attributes */
    private function event(Possession $possession, array $attributes = []): PossessionProvenanceEvent
    {
        return PossessionProvenanceEvent::factory()->create(['possession_id' => $possession->id, ...$attributes]);
    }
}
