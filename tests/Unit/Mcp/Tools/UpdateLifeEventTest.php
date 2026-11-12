<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Actions\CreateLifeEvent;
use App\Enums\FieldTypeEnum;
use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\UpdateLifeEvent;
use App\Models\Contact;
use App\Models\Field;
use App\Models\SectionVersion;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateLifeEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_what_it_is_given_and_keeps_the_rest(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $meal = $this->lifeEventTypeAsking($user, 'Meal', ['Dish' => FieldTypeEnum::Text]);
        $dish = $meal->structure->publishedVersion->inputFields()->firstOrFail();
        $lifeEvent = new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $jim,
            lifeEventType: $meal,
            happenedOn: CarbonImmutable::parse('2026-10-09'),
            title: 'Lunch at Cugino’s',
            location: 'Scranton',
            values: [$dish->id => 'Chicken parm'],
        )->execute();

        EricaServer::actingAs($user)->tool(UpdateLifeEvent::class, [
            'vault' => $vault->uuid,
            'life_event' => $lifeEvent->uuid,
            'happened_on' => '2026-10-10',
        ])->assertOk()->assertHasNoErrors();

        $lifeEvent->refresh();
        $this->assertEquals('2026-10-10', $lifeEvent->happened_on->toDateString());
        $this->assertEquals('Lunch at Cugino’s', $lifeEvent->title);
        $this->assertEquals('Scranton', $lifeEvent->location);
        $this->assertDatabaseHas('field_values', ['valuable_id' => $lifeEvent->id, 'field_id' => $dish->id, 'value_string' => 'Chicken parm']);
    }

    #[Test]
    public function it_keeps_the_values_when_the_fields_are_given_as_null(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $meal = $this->lifeEventTypeAsking($user, 'Meal', ['Dish' => FieldTypeEnum::Text]);
        $dish = $meal->structure->publishedVersion->inputFields()->firstOrFail();
        $lifeEvent = new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $jim,
            lifeEventType: $meal,
            happenedOn: CarbonImmutable::parse('2026-10-09'),
            values: [$dish->id => 'Chicken parm'],
        )->execute();

        EricaServer::actingAs($user)->tool(UpdateLifeEvent::class, [
            'vault' => $vault->uuid,
            'life_event' => $lifeEvent->uuid,
            'fields' => null,
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('field_values', ['valuable_id' => $lifeEvent->id, 'field_id' => $dish->id, 'value_string' => 'Chicken parm']);
    }

    #[Test]
    public function it_accepts_a_duration_written_as_a_string(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $lifeEvent = new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $jim,
            lifeEventType: $this->lifeEventTypeAsking($user, 'Meal'),
            happenedOn: CarbonImmutable::parse('2026-10-09'),
        )->execute();

        EricaServer::actingAs($user)->tool(UpdateLifeEvent::class, [
            'vault' => $vault->uuid,
            'life_event' => $lifeEvent->uuid,
            'duration_in_minutes' => '90',
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('life_events', ['id' => $lifeEvent->id, 'duration_in_minutes' => 90]);
    }

    #[Test]
    public function it_keeps_a_sensitive_value_it_cannot_see_when_the_fields_are_replaced(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $visit = $this->lifeEventTypeHolding($user, 'Doctor visit', function (SectionVersion $sectionVersion): void {
            Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Doctor', 'position' => 1]);
            Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Diagnosis', 'position' => 2, 'is_sensitive' => true]);
        });
        [$doctor, $diagnosis] = $visit->structure->publishedVersion->inputFields()->all();
        $lifeEvent = new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $jim,
            lifeEventType: $visit,
            happenedOn: CarbonImmutable::parse('2026-10-09'),
            values: [$doctor->id => 'Dr. Ken', $diagnosis->id => 'Vasectomy'],
        )->execute();

        EricaServer::actingAs($user)->tool(UpdateLifeEvent::class, [
            'vault' => $vault->uuid,
            'life_event' => $lifeEvent->uuid,
            'fields' => [$doctor->id => 'Dr. Jacobs'],
        ]);

        $this->assertDatabaseHas('field_values', ['valuable_id' => $lifeEvent->id, 'field_id' => $diagnosis->id, 'value_string' => 'Vasectomy']);
    }

    #[Test]
    public function it_refuses_a_life_event_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $nashua = Vault::factory()->create(['account_id' => $user->account_id]);
        $andy = Contact::factory()->create(['vault_id' => $nashua->id, 'account_id' => $user->account_id]);
        Queue::fake();
        $lifeEvent = new CreateLifeEvent(
            user: $user,
            vault: $nashua,
            contact: $andy,
            lifeEventType: $this->lifeEventTypeAsking($user, 'Concert'),
            happenedOn: CarbonImmutable::parse('2026-10-09'),
        )->execute();

        EricaServer::actingAs($user)->tool(UpdateLifeEvent::class, [
            'vault' => $vault->uuid,
            'life_event' => $lifeEvent->uuid,
            'happened_on' => '2026-10-10',
        ])->assertHasErrors(['That life event does not exist.']);
    }
}
