<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\SearchRecords;
use App\Enums\FieldTypeEnum;
use App\Models\Contact;
use App\Models\ContactNameRepresentation;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\Relationship;
use App\Models\RelationshipType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SearchRecordsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_finds_a_contact_holding_every_word_in_any_order(): void
    {
        $this->useSearchEngine();
        [$user, $vault] = $this->dunderMifflin();
        $dwight = $this->contact($vault, 'Dwight', 'Schrute', ['Employer' => 'Schrute Farms']);
        $this->contact($vault, 'Jim', 'Halpert', ['Employer' => 'Athlead']);
        $this->waitForSearchEngine();

        $this->assertSame([$dwight->id], $this->found($user, $vault, 'farms dwight'));
        $this->assertSame([], $this->found($user, $vault, 'farms jim'));
    }

    #[Test]
    public function it_finds_a_contact_despite_a_typing_mistake_or_a_missing_accent(): void
    {
        $this->useSearchEngine();
        [$user, $vault] = $this->dunderMifflin();
        $dwight = $this->contact($vault, 'Dwight', 'Schrute');
        $elodie = $this->contact($vault, 'Élodie', 'Martin');
        $this->waitForSearchEngine();

        $this->assertSame([$dwight->id], $this->found($user, $vault, 'Scrute'));
        $this->assertSame([$dwight->id], $this->found($user, $vault, 'Dwigth'));
        $this->assertSame([$elodie->id], $this->found($user, $vault, 'elodie'));
    }

    #[Test]
    public function it_ranks_a_name_match_above_a_value_match(): void
    {
        $this->useSearchEngine();
        [$user, $vault] = $this->dunderMifflin();
        $pam = $this->contact($vault, 'Pam', 'Beesly', ['City' => 'Scranton']);
        $strangler = $this->contact($vault, 'Scranton', 'Strangler');
        $this->waitForSearchEngine();

        $this->assertSame([$strangler->id, $pam->id], $this->found($user, $vault, 'scranton'));
    }

    #[Test]
    public function it_searches_the_current_vault_unless_told_to_look_across_the_account(): void
    {
        $this->useSearchEngine();
        [$user, $office] = $this->dunderMifflin();
        $farm = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Schrute Farms']);
        $atTheOffice = $this->contact($office, 'Dwight', 'Schrute');
        $atTheFarm = $this->contact($farm, 'Mose', 'Schrute');
        $this->waitForSearchEngine();

        $this->assertSame([$atTheOffice->id], $this->found($user, $office, 'schrute'));
        $this->assertEqualsCanonicalizing([$atTheOffice->id, $atTheFarm->id], $this->found($user, $office, 'schrute', acrossAccount: true));
    }

    #[Test]
    public function it_never_returns_a_record_of_another_account(): void
    {
        $this->useSearchEngine();
        [$user, $vault] = $this->dunderMifflin();
        [, $otherVault] = $this->dunderMifflin();
        $this->contact($otherVault, 'Dwight', 'Schrute');
        $this->waitForSearchEngine();

        $this->assertSame([], $this->found($user, $vault, 'schrute', acrossAccount: true));
    }

    #[Test]
    public function it_leaves_a_sensitive_value_out_of_reach_of_a_caller_who_may_not_read_it(): void
    {
        $this->useSearchEngine();
        [$user, $vault] = $this->dunderMifflin();
        $dwight = $this->contact($vault, 'Dwight', 'Schrute', sensitive: ['Beet recipe' => 'brine']);
        $this->waitForSearchEngine();

        $this->assertSame([$dwight->id], $this->found($user, $vault, 'brine'));
        $this->assertSame([], $this->found($user, $vault, 'brine', withSensitive: false));
    }

    #[Test]
    public function it_searches_the_database_and_leaves_a_sensitive_value_out_of_reach_there_too(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            $this->markTestSkipped('A full-text index sees only committed rows, and RefreshDatabase never commits.');
        }

        config(['scout.driver' => 'database']);
        [$user, $vault] = $this->dunderMifflin();
        $dwight = $this->contact($vault, 'Dwight', 'Schrute', sensitive: ['Beet recipe' => 'brine']);

        $this->assertSame([$dwight->id], $this->found($user, $vault, 'brine'));
        $this->assertSame([], $this->found($user, $vault, 'brine', withSensitive: false));
    }

    #[Test]
    public function it_finds_a_contact_once_by_each_way_its_name_is_written(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            $this->markTestSkipped('A full-text index sees only committed rows, and RefreshDatabase never commits.');
        }

        config(['scout.driver' => 'database']);
        [$user, $vault] = $this->dunderMifflin();
        $minji = $this->contact($vault, '민지', '김');
        ContactNameRepresentation::factory()->transliteration()->create(['contact_id' => $minji->id, 'formatted_name' => 'Kim Min-ji']);
        ContactNameRepresentation::factory()->create(['contact_id' => $minji->id, 'script_code' => 'Hani', 'formatted_name' => '金敏智', 'given_name' => null, 'family_name' => null]);
        $this->contact($vault, 'Jim', 'Halpert');

        $this->assertSame([$minji->id], $this->found($user, $vault, '민지'));
        $this->assertSame([$minji->id], $this->found($user, $vault, 'Min-ji'));
        $this->assertSame([$minji->id], $this->found($user, $vault, '金敏智'));
        $this->assertSame([], $this->found($user, $vault, 'Minji Gim'));
    }

    #[Test]
    public function it_groups_entities_under_their_type(): void
    {
        $this->useSearchEngine();
        [$user, $vault] = $this->dunderMifflin();
        $companies = EntityType::factory()->create(['account_id' => $user->account_id, 'name_plural' => 'Companies']);
        $farm = Entity::factory()->create(['vault_id' => $vault->id, 'entity_type_id' => $companies->id, 'name' => 'Schrute Farms']);
        $dwight = $this->contact($vault, 'Dwight', 'Schrute');
        $this->waitForSearchEngine();

        $groups = new SearchRecords(user: $user, vault: $vault, query: 'schrute')->execute()['groups'];

        $this->assertSame(['people', "entity-type-{$companies->slug}"], array_column($groups, 'key'));
        $this->assertSame('Companies', $groups[1]['label']);
        $this->assertSame([$dwight->id], $groups[0]['results']->pluck('id')->all());
        $this->assertSame([$farm->id], $groups[1]['results']->pluck('id')->all());
    }

    #[Test]
    public function it_leaves_out_a_deleted_record_and_finds_it_again_once_restored(): void
    {
        $this->useSearchEngine();
        [$user, $vault] = $this->dunderMifflin();
        $dwight = $this->contact($vault, 'Dwight', 'Schrute');
        $this->waitForSearchEngine();

        $dwight->delete();
        $this->waitForSearchEngine();
        $this->assertSame([], $this->found($user, $vault, 'schrute'));

        $dwight->restore();
        $this->waitForSearchEngine();
        $this->assertSame([$dwight->id], $this->found($user, $vault, 'schrute'));
    }

    #[Test]
    public function it_finds_what_happened_and_who_was_there(): void
    {
        $this->useSearchEngine();
        [$user, $vault] = $this->dunderMifflin();
        $jan = $this->contact($vault, 'Jan', 'Levinson');
        $michael = $this->contact($vault, 'Michael', 'Scott');
        $partner = RelationshipType::factory()->create(['account_id' => $user->account_id, 'role_a' => 'Partner', 'role_b' => 'Partner']);
        Relationship::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'relationship_type_id' => $partner->id, 'contact_a_id' => $michael->id, 'contact_b_id' => $jan->id]);
        $dinner = LifeEvent::factory()->create(['vault_id' => $vault->id, 'title' => 'Dinner at Chili’s', 'narrative' => 'The Dundie awards']);
        LifeEventParticipant::factory()->create(['account_id' => $user->account_id, 'life_event_id' => $dinner->id, 'participant_type' => Contact::class, 'participant_id' => $jan->id]);
        $this->waitForSearchEngine();

        $groups = collect(new SearchRecords(user: $user, vault: $vault, query: 'jan')->execute()['groups'])->keyBy('key');

        $this->assertSame(['people', 'life-events'], $groups->keys()->all());
        $this->assertSame([$jan->id, $michael->id], $groups['people']['results']->pluck('id')->all());
        $this->assertSame([$dinner->id], $groups['life-events']['results']->pluck('id')->all());

        $byWhatHappened = collect(new SearchRecords(user: $user, vault: $vault, query: 'dundie')->execute()['groups'])->keyBy('key');

        $this->assertSame([$dinner->id], $byWhatHappened['life-events']['results']->pluck('id')->all());
    }

    #[Test]
    public function it_does_not_search_a_term_under_two_letters(): void
    {
        [$user, $vault] = $this->dunderMifflin();

        $this->assertSame(SearchRecords::TOO_SHORT, new SearchRecords(user: $user, vault: $vault, query: ' d ! ')->execute()['status']);
    }

    #[Test]
    public function it_says_search_is_unavailable_without_an_engine_it_supports(): void
    {
        [$user, $vault] = $this->dunderMifflin();
        config(['scout.driver' => 'collection']);

        $this->assertSame(SearchRecords::UNAVAILABLE, new SearchRecords(user: $user, vault: $vault, query: 'schrute')->execute()['status']);
    }

    #[Test]
    public function it_says_search_is_unavailable_when_the_engine_cannot_be_reached(): void
    {
        [$user, $vault] = $this->dunderMifflin();
        $this->contact($vault, 'Dwight', 'Schrute');
        config(['scout.driver' => 'meilisearch', 'scout.meilisearch.host' => 'http://127.0.0.1:1']);

        $this->assertSame(SearchRecords::UNAVAILABLE, new SearchRecords(user: $user, vault: $vault, query: 'schrute')->execute()['status']);
    }

    #[Test]
    public function it_refuses_a_vault_of_another_account(): void
    {
        [$user] = $this->dunderMifflin();
        [, $otherVault] = $this->dunderMifflin();

        $this->expectException(ModelNotFoundException::class);

        new SearchRecords(user: $user, vault: $otherVault, query: 'schrute')->execute();
    }

    /** @return array{User, Vault} */
    private function dunderMifflin(): array
    {
        $user = User::factory()->create();

        return [$user, Vault::factory()->create(['account_id' => $user->account_id])];
    }

    /**
     * @param  array<string, string>  $values
     * @param  array<string, string>  $sensitive
     */
    private function contact(Vault $vault, string $firstName, string $lastName, array $values = [], array $sensitive = []): Contact
    {
        $contact = Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);

        foreach ([$values, $sensitive] as $isSensitive => $written) {
            foreach ($written as $label => $text) {
                $field = Field::factory()->create([
                    'account_id' => $vault->account_id,
                    'type' => FieldTypeEnum::Text,
                    'label' => $label,
                    'is_sensitive' => $isSensitive === 1,
                ]);

                FieldValue::factory()->create([
                    'valuable_type' => Contact::class,
                    'valuable_id' => $contact->id,
                    'account_id' => $vault->account_id,
                    'vault_id' => $vault->id,
                    'field_id' => $field->id,
                    'value_string' => $text,
                ]);
            }
        }

        return $contact;
    }

    /** @return list<int> */
    private function found(User $user, Vault $vault, string $query, bool $acrossAccount = false, bool $withSensitive = true): array
    {
        $result = new SearchRecords(user: $user, vault: $vault, query: $query, acrossAccount: $acrossAccount, withSensitive: $withSensitive)->execute();

        $this->assertSame(SearchRecords::SEARCHED, $result['status']);

        $people = collect($result['groups'])->firstWhere('key', 'people');

        return $people === null ? [] : $people['results']->pluck('id')->all();
    }
}
