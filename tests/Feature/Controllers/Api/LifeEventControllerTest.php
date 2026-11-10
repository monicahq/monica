<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Actions\CreateLifeEvent;
use App\Enums\FieldTypeEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\Group;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\LifeEventType;
use App\Models\SectionVersion;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LifeEventControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int|string, mixed> */
    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => [
            'name',
            'title',
            'life_event_type' => ['id', 'name'],
            'group',
            'happened_on',
            'duration_in_minutes',
            'narrative',
            'location',
            'participants',
            'fields',
            'created_from',
            'deleted_at',
            'created_at',
            'updated_at',
        ],
        'links' => ['self', 'web'],
    ];

    #[Test]
    public function it_lists_the_life_events_of_a_vault_most_recent_first(): void
    {
        [$user, $vault, $jim, $pam] = $this->office();
        $this->lifeEventOn($vault, '2026-03-01', [$jim], 'Basketball game');
        $this->lifeEventOn($vault, '2026-05-01', [$pam], 'Art show');
        $this->lifeEventOn($vault, '2026-04-01', [$jim], 'Office Olympics')->delete();

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/life-events");

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['*' => $this->jsonStructure], 'meta' => ['page', 'per_page', 'total']]);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.attributes.title', 'Art show');
        $response->assertJsonPath('data.1.attributes.title', 'Basketball game');
        $response->assertJsonPath('meta.total', 2);
    }

    #[Test]
    public function it_lists_the_life_events_one_contact_took_part_in(): void
    {
        [$user, $vault, $jim, $pam] = $this->office();
        $this->lifeEventOn($vault, '2026-03-01', [$jim, $pam], 'Booze cruise');
        $this->lifeEventOn($vault, '2026-05-01', [$pam], 'Art show');

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/life-events", ['contact' => $jim->uuid]);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.attributes.title', 'Booze cruise');
    }

    #[Test]
    public function it_does_not_find_a_vault_of_another_account(): void
    {
        [$user] = $this->office();
        [, $stamford, $karen] = $this->office();
        $this->lifeEventOn($stamford, '2026-03-01', [$karen], 'Branch merger');

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$stamford->uuid}/life-events")->assertNotFound();
    }

    #[Test]
    public function it_shows_a_life_event_with_who_was_there_and_its_fields(): void
    {
        [$user, $vault, $jim, $pam] = $this->office();
        $lifeEvent = $this->lifeEventOn($vault, '2026-03-01', [$jim, $pam], 'Booze cruise');
        $this->valueOn($lifeEvent, 'Boat', 'Lake Wallenpaupack', sensitive: false);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/life-events/{$lifeEvent->uuid}");

        $response->assertOk();
        $response->assertJsonStructure(['data' => $this->jsonStructure]);
        $response->assertJsonPath('data.attributes.happened_on', '2026-03-01');
        $response->assertJsonPath('data.attributes.participants.0.name', $jim->name);
        $response->assertJsonPath('data.attributes.participants.1.name', $pam->name);
        $response->assertJsonPath('data.attributes.fields.0.label', 'Boat');
        $response->assertJsonPath('data.attributes.fields.0.value', 'Lake Wallenpaupack');
        $response->assertJsonPath('data.links.web', route('contacts.show', [$vault, $jim]));
    }

    #[Test]
    public function it_hides_a_sensitive_value_from_a_key_without_the_scope(): void
    {
        [$user, $vault, $jim] = $this->office();
        $lifeEvent = $this->lifeEventOn($vault, '2026-03-01', [$jim], 'Doctor visit');
        $this->valueOn($lifeEvent, 'Diagnosis', 'Vasectomy', sensitive: true);

        $this->withToken($user->createToken('Dwight’s script')->plainTextToken)
            ->json('GET', "/api/v1/vaults/{$vault->uuid}/life-events/{$lifeEvent->uuid}")
            ->assertOk()
            ->assertJsonPath('data.attributes.fields.0.value', null)
            ->assertJsonPath('data.attributes.fields.0.is_hidden', true);
    }

    #[Test]
    public function it_shows_a_sensitive_value_to_a_key_holding_the_scope(): void
    {
        [$user, $vault, $jim] = $this->office();
        $lifeEvent = $this->lifeEventOn($vault, '2026-03-01', [$jim], 'Doctor visit');
        $this->valueOn($lifeEvent, 'Diagnosis', 'Vasectomy', sensitive: true);

        $this->withToken($user->createToken('Dwight’s trusted script', ['sensitive-fields'])->plainTextToken)
            ->json('GET', "/api/v1/vaults/{$vault->uuid}/life-events/{$lifeEvent->uuid}")
            ->assertOk()
            ->assertJsonPath('data.attributes.fields.0.value', 'Vasectomy')
            ->assertJsonPath('data.attributes.fields.0.is_hidden', false);
    }

    #[Test]
    public function it_does_not_find_a_life_event_through_another_vault_of_the_account(): void
    {
        [$user, $vault, $jim] = $this->office();
        $nashua = Vault::factory()->create(['account_id' => $user->account_id]);
        $lifeEvent = $this->lifeEventOn($vault, '2026-03-01', [$jim], 'Booze cruise');

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$nashua->uuid}/life-events/{$lifeEvent->uuid}")->assertNotFound();
    }

    #[Test]
    public function it_writes_down_a_life_event(): void
    {
        Queue::fake();
        [$user, $vault, $jim, $pam] = $this->office();
        $meal = $this->lifeEventTypeAsking($user, 'Meal', ['Dish' => FieldTypeEnum::Text]);
        $dish = $meal->structure->publishedVersion->inputFields()->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/life-events", [
            'life_event_type' => $meal->uuid,
            'happened_on' => '2026-10-09',
            'participants' => [$pam->uuid, $jim->uuid],
            'title' => 'Lunch at Cugino’s',
            'fields' => [$dish->id => 'Chicken parm'],
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['data' => $this->jsonStructure]);
        $response->assertJsonPath('data.attributes.created_from', 'api');
        $response->assertJsonPath('data.attributes.participants.0.name', $pam->name);
        $response->assertJsonPath('data.attributes.fields.0.value', 'Chicken parm');
        $this->assertDatabaseHas('life_events', ['vault_id' => $vault->id, 'title' => 'Lunch at Cugino’s', 'created_from' => 'api', 'participant_count' => 2]);
    }

    #[Test]
    public function it_validates_what_a_life_event_needs(): void
    {
        [$user, $vault] = $this->office();

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/life-events", ['participants' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['life_event_type', 'happened_on', 'participants']);
    }

    #[Test]
    public function it_refuses_a_value_for_a_file_field(): void
    {
        [$user, $vault, $jim] = $this->office();
        $party = $this->lifeEventTypeAsking($user, 'Party', ['Invitation' => FieldTypeEnum::File]);
        $invitation = $party->structure->publishedVersion->inputFields()->firstOrFail();

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/life-events", [
            'life_event_type' => $party->uuid,
            'happened_on' => '2026-10-09',
            'participants' => [$jim->uuid],
            'fields' => [$invitation->id => 'invitation.pdf'],
        ])->assertUnprocessable()->assertJsonValidationErrors(["fields.{$invitation->id}"]);
    }

    #[Test]
    public function it_refuses_a_type_that_can_no_longer_be_chosen(): void
    {
        Queue::fake();
        [$user, $vault, $jim] = $this->office();
        $funRun = $this->lifeEventTypeAsking($user, 'Fun run');
        $funRun->update(['is_active' => false]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/life-events", [
            'life_event_type' => $funRun->uuid,
            'happened_on' => '2026-10-09',
            'participants' => [$jim->uuid],
        ])->assertUnprocessable()->assertJsonValidationErrors(['life_event_type']);
    }

    #[Test]
    public function it_corrects_a_life_event_and_keeps_its_values_when_none_are_sent(): void
    {
        Queue::fake();
        [$user, $vault, $jim, $pam] = $this->office();
        $meal = $this->lifeEventTypeAsking($user, 'Meal', ['Dish' => FieldTypeEnum::Text]);
        $dish = $meal->structure->publishedVersion->inputFields()->firstOrFail();
        $lifeEvent = $this->recorded($user, $vault, $jim, $meal, [$dish->id => 'Chicken parm']);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/life-events/{$lifeEvent->uuid}", [
            'life_event_type' => $meal->uuid,
            'happened_on' => '2026-10-10',
            'participants' => [$jim->uuid, $pam->uuid],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.happened_on', '2026-10-10');
        $response->assertJsonPath('data.attributes.participants.1.name', $pam->name);
        $this->assertDatabaseHas('field_values', ['valuable_id' => $lifeEvent->id, 'field_id' => $dish->id, 'value_string' => 'Chicken parm']);
    }

    #[Test]
    public function it_keeps_the_values_when_the_fields_are_sent_as_null(): void
    {
        Queue::fake();
        [$user, $vault, $jim] = $this->office();
        $meal = $this->lifeEventTypeAsking($user, 'Meal', ['Dish' => FieldTypeEnum::Text]);
        $dish = $meal->structure->publishedVersion->inputFields()->firstOrFail();
        $lifeEvent = $this->recorded($user, $vault, $jim, $meal, [$dish->id => 'Chicken parm']);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/life-events/{$lifeEvent->uuid}", [
            'life_event_type' => $meal->uuid,
            'happened_on' => '2026-10-10',
            'participants' => [$jim->uuid],
            'fields' => null,
        ])->assertOk();

        $this->assertDatabaseHas('field_values', ['valuable_id' => $lifeEvent->id, 'field_id' => $dish->id, 'value_string' => 'Chicken parm']);
    }

    #[Test]
    public function it_keeps_a_sensitive_value_the_key_cannot_see_when_the_fields_are_replaced(): void
    {
        Queue::fake();
        [$user, $vault, $jim] = $this->office();
        $visit = $this->lifeEventTypeHolding($user, 'Doctor visit', function (SectionVersion $sectionVersion): void {
            Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Doctor', 'position' => 1]);
            Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Diagnosis', 'position' => 2, 'is_sensitive' => true]);
        });
        [$doctor, $diagnosis] = $visit->structure->publishedVersion->inputFields()->all();
        $lifeEvent = $this->recorded($user, $vault, $jim, $visit, [$doctor->id => 'Dr. Ken', $diagnosis->id => 'Vasectomy']);

        $this->withToken($user->createToken('Dwight’s script')->plainTextToken)
            ->json('PUT', "/api/v1/vaults/{$vault->uuid}/life-events/{$lifeEvent->uuid}", [
                'life_event_type' => $visit->uuid,
                'happened_on' => '2026-10-09',
                'participants' => [$jim->uuid],
                'fields' => [$doctor->id => 'Dr. Jacobs'],
            ]);

        $this->assertDatabaseHas('field_values', ['valuable_id' => $lifeEvent->id, 'field_id' => $diagnosis->id, 'value_string' => 'Vasectomy']);
    }

    #[Test]
    public function it_keeps_a_repetition_sent_back_under_the_row_the_life_event_gives(): void
    {
        Queue::fake();
        [$user, $vault, $jim] = $this->office();
        FieldValueRow::factory()->create();
        $party = $this->lifeEventTypeHolding($user, 'Party', function (SectionVersion $sectionVersion): void {
            $guests = Field::factory()->repeater()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Guests']);
            Field::factory()->create(['section_version_id' => $sectionVersion->id, 'parent_field_id' => $guests->id, 'label' => 'Guest']);
        });
        $guests = $party->structure->publishedVersion->inputFields()->firstOrFail();
        $guest = $guests->children->firstOrFail();
        $lifeEvent = $this->recorded($user, $vault, $jim, $party, [$guests->id => [['values' => [$guest->id => 'Dwight']]]]);
        $repetition = FieldValueRow::query()->where('valuable_id', $lifeEvent->id)->firstOrFail();

        Sanctum::actingAs($user);

        $row = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/life-events/{$lifeEvent->uuid}")
            ->json('data.attributes.fields.0.row');

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/life-events/{$lifeEvent->uuid}", [
            'life_event_type' => $party->uuid,
            'happened_on' => '2026-10-09',
            'participants' => [$jim->uuid],
            'fields' => [$guests->id => [['id' => $row, 'values' => [$guest->id => 'Dwight Schrute']]]],
        ])->assertOk();

        $this->assertModelExists($repetition);
        $this->assertDatabaseHas('field_values', ['field_value_row_id' => $repetition->id, 'field_id' => $guest->id, 'value_string' => 'Dwight Schrute']);
    }

    #[Test]
    public function it_refuses_to_record_a_life_event_for_somebody_else(): void
    {
        Queue::fake();
        [$user, $vault, $jim, $pam] = $this->office();
        $meal = $this->lifeEventTypeAsking($user, 'Meal');
        $lifeEvent = $this->recorded($user, $vault, $jim, $meal);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/life-events/{$lifeEvent->uuid}", [
            'life_event_type' => $meal->uuid,
            'happened_on' => '2026-10-10',
            'participants' => [$pam->uuid],
        ])->assertUnprocessable()->assertJsonValidationErrors(['participants']);
    }

    #[Test]
    public function it_deletes_a_life_event(): void
    {
        Queue::fake();
        [$user, $vault, $jim] = $this->office();
        $lifeEvent = $this->recorded($user, $vault, $jim, $this->lifeEventTypeAsking($user, 'Meal'));

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}/life-events/{$lifeEvent->uuid}")->assertNoContent();

        $this->assertSoftDeleted($lifeEvent);
    }

    #[Test]
    public function it_reports_no_group_once_the_group_is_deleted(): void
    {
        [$user, $vault, $jim] = $this->office();
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $lifeEvent = $this->lifeEventOn($vault, '2026-03-01', [$jim], 'Basketball game');
        $lifeEvent->update(['group_id' => $group->id]);
        $group->delete();

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/life-events/{$lifeEvent->uuid}")
            ->assertOk()
            ->assertJsonPath('data.attributes.group', null);
    }

    /** @return array{0: User, 1: Vault, 2: Contact, 3: Contact} */
    private function office(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $person = fn (string $name): Contact => Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => $name]);

        return [$user, $vault, $person('Jim'), $person('Pam')];
    }

    /** @param  list<Contact>  $people */
    private function lifeEventOn(Vault $vault, string $on, array $people, string $title): LifeEvent
    {
        $lifeEvent = LifeEvent::factory()->create(['vault_id' => $vault->id, 'happened_on' => $on, 'title' => $title, 'participant_count' => count($people)]);

        foreach ($people as $position => $person) {
            LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id, 'participant_id' => $person->id, 'position' => $position]);
        }

        return $lifeEvent;
    }

    private function valueOn(LifeEvent $lifeEvent, string $label, string $text, bool $sensitive): void
    {
        $field = Field::factory()->create(['account_id' => $lifeEvent->account_id, 'type' => FieldTypeEnum::Text, 'label' => $label, 'is_sensitive' => $sensitive]);

        FieldValue::factory()->create([
            'valuable_type' => LifeEvent::class,
            'valuable_id' => $lifeEvent->id,
            'account_id' => $lifeEvent->account_id,
            'vault_id' => $lifeEvent->vault_id,
            'field_id' => $field->id,
            'value_string' => $text,
        ]);
    }

    /** @param  array<int, mixed>  $values */
    private function recorded(User $user, Vault $vault, Contact $contact, LifeEventType $type, array $values = []): LifeEvent
    {
        return new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $contact,
            lifeEventType: $type,
            happenedOn: CarbonImmutable::parse('2026-10-09'),
            values: $values,
        )->execute();
    }
}
