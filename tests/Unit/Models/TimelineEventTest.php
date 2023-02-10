<?php

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\TimelineEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TimelineEventTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_contact()
    {
        $timeline = TimelineEvent::factory()->create();

        $this->assertTrue($timeline->vault()->exists());
    }

    /** @test */
    public function it_has_many_life_events(): void
    {
        $timeline = TimelineEvent::factory()->create();
        LifeEvent::factory()->count(2)->create([
            'timeline_event_id' => $timeline->id,
        ]);

        $this->assertTrue($timeline->lifeEvents()->exists());
    }

    /** @test */
    public function it_has_many_participants(): void
    {
        $contact = Contact::factory()->create();
        $timeline = TimelineEvent::factory()->create();

        $timeline->participants()->sync([$contact->id]);

        $this->assertTrue($timeline->participants()->exists());
    }

    /** @test */
    public function it_gets_the_date_range(): void
    {
        $user = User::factory()->create();
        $this->be($user);
        $timeline = TimelineEvent::factory()->create();
        LifeEvent::factory()->create([
            'timeline_event_id' => $timeline->id,
            'happened_at' => '2020-01-01',
        ]);

        $this->assertEquals(
            'Jan 01, 2020',
            $timeline->range
        );

        LifeEvent::factory()->create([
            'timeline_event_id' => $timeline->id,
            'happened_at' => '2019-01-01',
        ]);

        $this->assertEquals(
            'Jan 01, 2019 — Jan 01, 2020',
            $timeline->range
        );

        LifeEvent::factory()->create([
            'timeline_event_id' => $timeline->id,
            'happened_at' => '2019-03-01',
        ]);

        $this->assertEquals(
            'Jan 01, 2019 — Jan 01, 2020',
            $timeline->range
        );
    }
}
