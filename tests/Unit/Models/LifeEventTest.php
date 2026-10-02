<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\LifeEventType;
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
}
