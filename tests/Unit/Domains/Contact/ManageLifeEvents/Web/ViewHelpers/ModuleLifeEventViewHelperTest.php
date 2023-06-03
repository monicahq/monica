<?php

namespace Tests\Unit\Domains\Contact\ManageLifeEvents\Web\ViewHelpers;

use App\Domains\Contact\ManageLifeEvents\Web\ViewHelpers\ModuleLifeEventViewHelper;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\TimelineEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class ModuleLifeEventViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $contact = Contact::factory()->create();
        $user = User::factory()->create();

        $array = ModuleLifeEventViewHelper::data($contact, $user);

        $this->assertEquals(
            5,
            count($array)
        );

        $this->assertArrayHasKey('contact', $array);
        $this->assertArrayHasKey('current_date', $array);
        $this->assertArrayHasKey('current_date_human_format', $array);
        $this->assertArrayHasKey('life_event_categories', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            '2018-01-01',
            $array['current_date']
        );
        $this->assertEquals(
            'Jan 01, 2018',
            $array['current_date_human_format']
        );

        $this->assertEquals(
            [
                'load' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/timelineEvents',
                'store' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/timelineEvents',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_transfer_object_for_categories(): void
    {
        $contact = Contact::factory()->create();
        $user = User::factory()->create();
        $lifeEventCategory = LifeEventCategory::factory()->create([
            'vault_id' => $contact->vault_id,
            'label' => 'name',
        ]);

        $array = ModuleLifeEventViewHelper::dtoLifeEventCategory($lifeEventCategory);

        $this->assertEquals(
            3,
            count($array)
        );
        $this->assertEquals(
            $lifeEventCategory->id,
            $array['id']
        );
        $this->assertEquals(
            'name',
            $array['label']
        );
        $this->assertEquals(
            [],
            $array['life_event_types']->toArray()
        );
    }

    /** @test */
    public function it_gets_the_data_transfer_object_for_types(): void
    {
        $contact = Contact::factory()->create();
        $lifeEventCategory = LifeEventCategory::factory()->create([
            'vault_id' => $contact->vault_id,
            'label' => 'name',
        ]);
        $lifeEventType = LifeEventType::factory()->create([
            'life_event_category_id' => $lifeEventCategory->id,
            'label' => 'name',
        ]);

        $array = ModuleLifeEventViewHelper::dtoLifeEventType($lifeEventType);

        $this->assertEquals([
            'id' => $lifeEventType->id,
            'label' => $lifeEventType->label,
        ],
            $array
        );
    }

    /** @test */
    public function it_gets_the_data_transfer_object_for_the_timeline_objects(): void
    {
        $contact = Contact::factory()->create();
        $user = User::factory()->create();
        $lifeEvents = $contact
            ->lifeEvents()
            ->get();

        $array = ModuleLifeEventViewHelper::timelineEvents($lifeEvents, $user, $contact);

        $this->assertEquals(
            1,
            count($array)
        );
        $this->assertArrayHasKey('timeline_events', $array);
    }

    /** @test */
    public function it_gets_the_data_transfer_object_for_the_timeline_event(): void
    {
        $contact = Contact::factory()->create();
        $user = User::factory()->create();
        $timelineEvent = TimelineEvent::factory()->create([
            'vault_id' => $contact->vault_id,
            'label' => 'test',
        ]);

        $array = ModuleLifeEventViewHelper::dtoTimelineEvent($timelineEvent, $user, $contact);

        $this->assertEquals(
            6,
            count($array)
        );
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertArrayHasKey('collapsed', $array);
        $this->assertArrayHasKey('happened_at', $array);
        $this->assertArrayHasKey('life_events', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            $timelineEvent->id,
            $array['id']
        );
        $this->assertEquals(
            'test',
            $array['label']
        );
        $this->assertEquals(
            [
                'store' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/timelineEvents/'.$timelineEvent->id.'/lifeEvents',
                'toggle' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/timelineEvents/'.$timelineEvent->id.'/toggle',
                'destroy' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/timelineEvents/'.$timelineEvent->id,
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_transfer_object_for_the_life_event(): void
    {
        $contact = Contact::factory()->create();
        $user = User::factory()->create();
        $timelineEvent = TimelineEvent::factory()->create([
            'vault_id' => $contact->vault_id,
            'label' => 'test',
        ]);
        $lifeEventCategory = LifeEventCategory::factory()->create([
            'label' => 'test',
        ]);
        $lifeEventType = LifeEventType::factory()->create([
            'life_event_category_id' => $lifeEventCategory->id,
            'label' => 'test',
        ]);
        $lifeEvent = LifeEvent::factory()->create([
            'life_event_type_id' => $lifeEventType->id,
            'timeline_event_id' => $timelineEvent->id,
            'happened_at' => '2018-01-01',
        ]);

        $array = ModuleLifeEventViewHelper::dtoLifeEvent($lifeEvent, $user, $contact);

        $this->assertEquals(
            20,
            count($array)
        );

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('emotion_id', $array);
        $this->assertArrayHasKey('collapsed', $array);
        $this->assertArrayHasKey('summary', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('happened_at', $array);
        $this->assertArrayHasKey('costs', $array);
        $this->assertArrayHasKey('currency_id', $array);
        $this->assertArrayHasKey('paid_by_contact_id', $array);
        $this->assertArrayHasKey('duration_in_minutes', $array);
        $this->assertArrayHasKey('distance', $array);
        $this->assertArrayHasKey('distance_unit', $array);
        $this->assertArrayHasKey('from_place', $array);
        $this->assertArrayHasKey('to_place', $array);
        $this->assertArrayHasKey('place', $array);
        $this->assertArrayHasKey('participants', $array);
        $this->assertArrayHasKey('timeline_event', $array);
        $this->assertArrayHasKey('life_event_type', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            $lifeEvent->id,
            $array['id']
        );
    }
}
