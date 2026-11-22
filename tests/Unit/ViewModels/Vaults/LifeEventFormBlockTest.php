<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Actions\CreateLifeEvent;
use App\Actions\ReplaceLifeEventTypeFields;
use App\Enums\FieldTypeEnum;
use App\Models\Contact;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\LifeEventFormBlock;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LifeEventFormBlockTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_active_categories_and_types_in_their_order(): void
    {
        [$user, $vault, $jim] = $this->jim();
        $work = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Work', 'position' => 2]);
        $home = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Home', 'position' => 1]);
        LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Retired', 'is_active' => false]);
        LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $work->id, 'name' => 'Sales call', 'position' => 2]);
        LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $work->id, 'name' => 'Meeting', 'position' => 1]);
        LifeEventType::factory()->inactive()->create(['account_id' => $user->account_id, 'life_event_category_id' => $work->id, 'name' => 'Fax']);
        LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $home->id, 'name' => 'Move']);

        $categories = $this->block($user, $vault, $jim)->categories();

        $this->assertEquals(['Home', 'Work'], array_column($categories, 'name'));
        $this->assertEquals(['Meeting', 'Sales call'], array_column($categories[1]['types'], 'name'));
        $this->assertEquals(2, $categories[1]['typeCount']);
    }

    #[Test]
    public function it_leaves_the_catalogue_of_types_to_the_page_that_asks_for_the_panel(): void
    {
        [$user, $vault, $jim] = $this->jim();
        $work = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Work']);
        LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $work->id, 'name' => 'Sales call']);

        $closed = $this->block($user, $vault, $jim)->projection();

        $this->assertFalse($this->block($user, $vault, $jim)->isRendered());
        $this->assertFalse($closed['open']);
        $this->assertEquals([], $closed['types']);
        $this->assertEquals(route('contacts.lifeEvents.new', ['vault' => $vault, 'contact' => $jim]), $closed['newUrl']);

        $askedFor = new LifeEventFormBlock(user: $user, vault: $vault, contact: $jim, isAskedFor: true);
        $projection = $askedFor->projection();

        $this->assertTrue($askedFor->isRendered());
        $this->assertTrue($projection['open']);
        $this->assertEquals('pick', $projection['step']);
        $this->assertEquals(['Sales call'], array_column($projection['types'], 'name'));
        $this->assertNull($projection['newUrl']);
    }

    #[Test]
    public function it_reopens_where_the_reader_was_when_the_form_came_back_refused(): void
    {
        Queue::fake();

        [$user, $vault, $jim] = $this->jim();
        $call = $this->lifeEventTypeAsking($user, 'Call', ['Channel' => FieldTypeEnum::Text]);
        $pam = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Pam', 'last_name' => 'Beesly']);
        $channel = $call->structure->publishedVersion->inputFields()->firstOrFail();

        $this->withOldInput([
            'life_event_type_id' => (string) $call->id,
            'participants' => [(string) $pam->id],
            'title' => 'Call about the wedding',
            'fields' => [$channel->id => 'Phone'],
        ]);

        $projection = $this->block($user, $vault, $jim)->projection();

        $this->assertEquals('details', $projection['step']);
        $this->assertTrue($projection['open']);
        $this->assertNull($projection['newUrl']);
        $this->assertEquals('Call', $projection['type']['name']);
        $this->assertEquals([['id' => $pam->id, 'name' => 'Pam Beesly']], $projection['people']);
        $this->assertEquals(['title', "field-{$channel->id}"], $projection['openPieces']);
        $this->assertEquals('Phone', $this->block($user, $vault, $jim)->fieldsOf($call)[0]['rawValue']?->value_string);
    }

    #[Test]
    public function it_does_not_bring_back_somebody_from_another_vault(): void
    {
        [$user, $vault, $jim] = $this->jim();
        $stamford = Vault::factory()->create(['account_id' => $user->account_id]);
        $karen = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $stamford->id]);

        $this->withOldInput(['participants' => [(string) $karen->id]]);

        $this->assertEquals([], $this->block($user, $vault, $jim)->oldParticipants());
    }

    #[Test]
    public function it_lists_the_people_in_the_order_they_were_chosen(): void
    {
        [$user, $vault, $jim] = $this->jim();
        $pam = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Pam', 'last_name' => 'Beesly']);
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Dwight', 'last_name' => 'Schrute']);

        $this->withOldInput(['participants' => [(string) $dwight->id, (string) $pam->id]]);

        $this->assertSame([
            ['id' => $dwight->id, 'name' => 'Dwight Schrute'],
            ['id' => $pam->id, 'name' => 'Pam Beesly'],
        ], $this->block($user, $vault, $jim)->oldParticipants());
    }

    #[Test]
    public function it_opens_filled_in_for_the_life_event_being_corrected(): void
    {
        Queue::fake();

        [$user, $vault, $jim] = $this->jim();
        $pam = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Pam', 'last_name' => 'Beesly']);
        $call = $this->lifeEventTypeAsking($user, 'Call', ['Channel' => FieldTypeEnum::Text, 'Mood' => FieldTypeEnum::Text]);
        [$channel] = $call->structure->publishedVersion->inputFields()->all();
        $lifeEvent = new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $jim,
            lifeEventType: $call,
            happenedOn: CarbonImmutable::parse('2026-08-30'),
            title: 'Call about the move',
            participants: [$pam],
            values: [$channel->id => 'Mobile'],
        )->execute();

        $block = new LifeEventFormBlock(user: $user, vault: $vault, contact: $jim, lifeEvent: $lifeEvent);
        $projection = $block->projection();

        $this->assertEquals('put', $block->method());
        $this->assertEquals('details', $projection['step']);
        $this->assertEquals('2026-08-30', $projection['date']);
        $this->assertEquals([['id' => $pam->id, 'name' => 'Pam Beesly']], $projection['people']);
        $this->assertEquals(['title', "field-{$channel->id}"], $projection['openPieces']);
        $this->assertEquals('Call about the move', $block->pieceValue('title'));
        $this->assertEquals('Mobile', $block->fieldsOf($call)[0]['rawValue']?->value_string);
    }

    #[Test]
    public function it_offers_the_fields_the_life_event_was_written_with_after_its_type_changed(): void
    {
        Queue::fake();

        [$user, $vault, $jim] = $this->jim();
        $call = $this->lifeEventTypeAsking($user, 'Call', ['Channel' => FieldTypeEnum::Text]);
        $lifeEvent = new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $jim,
            lifeEventType: $call,
            happenedOn: CarbonImmutable::parse('2026-08-30'),
        )->execute();

        new ReplaceLifeEventTypeFields(user: $user, lifeEventType: $call, groups: [], fields: [])->execute();

        $block = new LifeEventFormBlock(user: $user, vault: $vault, contact: $jim, lifeEvent: $lifeEvent);

        $this->assertEquals(['Channel'], array_column($block->fieldsOf($call->refresh()), 'label'));
    }

    #[Test]
    public function it_records_for_the_contact_of_the_page_it_is_on(): void
    {
        [$user, $vault, $jim] = $this->jim();

        $block = $this->block($user, $vault, $jim);
        $projection = $block->projection();

        $this->assertFalse($block->choosesSubject());
        $this->assertEquals(route('contacts.lifeEvents.create', ['vault' => $vault, 'contact' => $jim]), $block->action());
        $this->assertEquals(route('contacts.lifeEvents.new', ['vault' => $vault, 'contact' => $jim]), $projection['newUrl']);
        $this->assertEquals(['id' => $jim->id, 'name' => 'Jim'], $projection['subject']);
    }

    #[Test]
    public function it_records_from_the_dashboard_for_whoever_is_chosen(): void
    {
        [$user, $vault, $michael] = $this->jim();

        $block = new LifeEventFormBlock(user: $user, vault: $vault, contact: $michael, choosesSubject: true);
        $projection = $block->projection();

        $this->assertTrue($block->choosesSubject());
        $this->assertEquals(route('vaults.lifeEvents.create', $vault), $block->action());
        $this->assertEquals(route('vaults.lifeEvents.new', $vault), $projection['newUrl']);
        $this->assertEquals(route('vaults.lifeEventCandidates.index', $vault), $projection['candidatesUrl']);
        $this->assertEquals(['id' => $michael->id, 'name' => 'Jim'], $projection['subject']);
    }

    #[Test]
    public function it_brings_back_the_person_chosen_on_the_dashboard_when_the_form_is_refused(): void
    {
        [$user, $vault, $michael] = $this->jim();
        $pam = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Pam', 'last_name' => 'Beesly']);

        $this->withOldInput(['contact_id' => (string) $pam->id, 'participants' => [(string) $michael->id]]);

        $projection = new LifeEventFormBlock(user: $user, vault: $vault, contact: $michael, choosesSubject: true)->projection();

        $this->assertEquals(['id' => $pam->id, 'name' => 'Pam'], $projection['subject']);
        $this->assertEquals([['id' => $michael->id, 'name' => $michael->name]], $projection['people']);
    }

    #[Test]
    public function it_does_not_bring_back_a_person_chosen_in_another_vault(): void
    {
        [$user, $vault, $michael] = $this->jim();
        $stamford = Vault::factory()->create(['account_id' => $user->account_id]);
        $karen = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $stamford->id]);

        $this->withOldInput(['contact_id' => (string) $karen->id]);

        $projection = new LifeEventFormBlock(user: $user, vault: $vault, contact: $michael, choosesSubject: true)->projection();

        $this->assertEquals($michael->id, $projection['subject']['id']);
    }

    /** @param  array<string, mixed>  $input */
    private function withOldInput(array $input): void
    {
        $session = $this->app['session.store'];
        $session->flashInput($input);

        request()->setLaravelSession($session);
    }

    /** @return array{0: User, 1: Vault, 2: Contact} */
    private function jim(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Jim']);

        return [$user, $vault, $jim];
    }

    private function block(User $user, Vault $vault, Contact $contact): LifeEventFormBlock
    {
        return new LifeEventFormBlock(user: $user, vault: $vault, contact: $contact);
    }
}
