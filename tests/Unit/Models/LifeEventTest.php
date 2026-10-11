<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\DateFormatEnum;
use App\Models\Contact;
use App\Models\ContactNameRepresentation;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LifeEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_is_called_by_its_title(): void
    {
        $lifeEvent = LifeEvent::factory()->create(['title' => 'Dinner party at Michael’s condo']);

        $this->assertEquals('Dinner party at Michael’s condo', $lifeEvent->name);
    }

    #[Test]
    public function it_is_called_by_its_type_when_it_has_no_title(): void
    {
        $lifeEvent = LifeEvent::factory()->create(['title' => null]);

        LifeEventType::query()->whereKey($lifeEvent->life_event_type_id)->update(['name' => 'Booze cruise']);

        $this->assertEquals('Booze cruise', $lifeEvent->fresh()?->name);
    }

    #[Test]
    public function it_sends_the_search_engine_every_name_of_who_was_there(): void
    {
        $dinner = LifeEvent::factory()->create(['title' => 'Dinner party']);
        $jan = Contact::factory()->create(['account_id' => $dinner->account_id, 'vault_id' => $dinner->vault_id, 'first_name' => 'Jan', 'last_name' => 'Levinson']);
        ContactNameRepresentation::factory()->transliteration()->create(['contact_id' => $jan->id, 'formatted_name' => 'ジャン・レビンソン']);
        LifeEventParticipant::factory()->create(['account_id' => $dinner->account_id, 'life_event_id' => $dinner->id, 'participant_type' => Contact::class, 'participant_id' => $jan->id]);

        $this->assertSame([['name' => "Jan Levinson\nジャン・レビンソン", 'label' => 'With Jan Levinson']], $dinner->refresh()->searchableLinkedNames());
    }

    #[Test]
    public function it_sends_the_search_engine_what_happened_where_and_who_was_there(): void
    {
        $dinner = LifeEvent::factory()->create(['title' => 'Dinner party', 'narrative' => 'Michael burnt the osso buco', 'location' => 'The condo']);
        $jan = Contact::factory()->create(['account_id' => $dinner->account_id, 'vault_id' => $dinner->vault_id, 'first_name' => 'Jan', 'last_name' => 'Levinson']);
        LifeEventParticipant::factory()->create(['account_id' => $dinner->account_id, 'life_event_id' => $dinner->id, 'participant_type' => Contact::class, 'participant_id' => $jan->id]);

        $document = $dinner->refresh()->toSearchableArray();

        $this->assertSame('Dinner party', $document['name']);
        $this->assertStringContainsString('osso buco', (string) $document['body']);
        $this->assertStringContainsString('The condo', (string) $document['body']);
        $this->assertSame('Jan Levinson', $document['linked_names']);
        $this->assertSame([['name' => 'Jan Levinson', 'label' => 'With Jan Levinson']], $dinner->searchableLinkedNames());
    }

    #[Test]
    public function it_words_the_confirmation_for_the_reader(): void
    {
        $promotion = LifeEvent::factory()->create(['title' => null, 'happened_on' => '2026-10-11']);
        LifeEventType::query()->whereKey($promotion->life_event_type_id)->update(['name' => 'Promotion']);
        $michael = Contact::factory()->create(['account_id' => $promotion->account_id, 'vault_id' => $promotion->vault_id, 'first_name' => 'Michael']);
        $dwight = Contact::factory()->create(['account_id' => $promotion->account_id, 'vault_id' => $promotion->vault_id, 'first_name' => 'Dwight']);
        LifeEventParticipant::factory()->create(['account_id' => $promotion->account_id, 'life_event_id' => $promotion->id, 'participant_type' => Contact::class, 'participant_id' => $michael->id, 'position' => 0]);
        LifeEventParticipant::factory()->create(['account_id' => $promotion->account_id, 'life_event_id' => $promotion->id, 'participant_type' => Contact::class, 'participant_id' => $dwight->id, 'position' => 1]);
        $reader = User::factory()->create(['date_format' => DateFormatEnum::YearFirst]);

        $this->assertSame(
            ['type' => 'Promotion', 'date' => '2026-10-11', 'names' => 'Michael and Dwight'],
            $promotion->fresh()?->confirmationParts($reader),
        );
    }
}
