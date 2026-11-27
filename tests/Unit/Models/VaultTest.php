<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\AccountImportStatusEnum;
use App\Enums\ContactGroupingEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\ValueListEnum;
use App\Models\Account;
use App\Models\AccountImport;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\KnowledgeFolder;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\ShareLink;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_holds_the_private_links_opened_onto_itself(): void
    {
        $vault = Vault::factory()->create();
        $ontoTheVault = ShareLink::factory()->forVault($vault)->create();
        ShareLink::factory()->create(['vault_id' => $vault->id]);

        $this->assertCount(1, $vault->shareLinks);
        $this->assertTrue($ontoTheVault->is($vault->shareLinks->first()));
    }

    #[Test]
    public function it_gets_a_readable_address_built_from_its_name(): void
    {
        $vault = Vault::factory()->create(['name' => 'Famille Halpert']);

        $this->assertSame('famille-halpert', $vault->slug);
        $this->assertDatabaseHas('vault_slug_reservations', [
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'slug' => 'famille-halpert',
        ]);
    }

    #[Test]
    public function it_numbers_a_second_vault_of_the_same_name(): void
    {
        $account = Account::factory()->create();

        $first = Vault::factory()->create(['account_id' => $account->id, 'name' => 'Famille']);
        $second = Vault::factory()->create(['account_id' => $account->id, 'name' => 'Famille']);

        $this->assertSame('famille', $first->slug);
        $this->assertSame('famille-2', $second->slug);
    }

    #[Test]
    public function it_lets_another_account_hold_the_same_readable_address(): void
    {
        $mine = Vault::factory()->create(['name' => 'Famille']);
        $theirs = Vault::factory()->create(['name' => 'Famille']);

        $this->assertNotSame($mine->account_id, $theirs->account_id);
        $this->assertSame('famille', $mine->slug);
        $this->assertSame('famille', $theirs->slug);
    }

    #[Test]
    public function it_refuses_a_name_a_route_already_fixes(): void
    {
        $vault = Vault::factory()->create(['name' => 'New']);

        $this->assertSame('new-2', $vault->slug);
    }

    #[Test]
    public function a_vault_named_with_digits_resolves_to_itself_rather_than_to_the_row_of_that_number(): void
    {
        $user = User::factory()->create();
        $decoy = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Decoy']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => (string) $decoy->id]);

        $this->actingAs($user);

        $this->assertTrue($vault->is(new Vault()->resolveRouteBinding((string) $decoy->id)));
    }

    #[Test]
    public function it_is_reached_by_its_readable_address_and_by_its_identifier(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Famille']);

        $this->actingAs($user);

        $this->assertTrue($vault->is(new Vault()->resolveRouteBinding('famille')));
        $this->assertTrue($vault->is(new Vault()->resolveRouteBinding($vault->uuid)));
    }

    #[Test]
    public function renaming_a_vault_rebuilds_its_readable_address(): void
    {
        $vault = Vault::factory()->create(['name' => 'Famille']);

        $vault->name = 'Famille Halpert';
        $vault->save();

        $this->assertSame('famille-halpert', $vault->fresh()->slug);
    }

    #[Test]
    public function a_readable_address_a_vault_has_released_is_refused_to_every_later_vault(): void
    {
        $account = Account::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $account->id, 'name' => 'Famille']);

        $vault->name = 'Amis';
        $vault->save();

        $later = Vault::factory()->create(['account_id' => $account->id, 'name' => 'Famille']);

        $this->assertSame('amis', $vault->fresh()->slug);
        $this->assertSame('famille-2', $later->slug);
    }

    #[Test]
    public function a_save_that_touches_no_name_leaves_the_readable_address_alone(): void
    {
        $vault = Vault::factory()->create(['name' => 'Famille']);

        $vault->description = 'Everybody at home';
        $vault->save();

        $this->assertSame('famille', $vault->fresh()->slug);
    }

    #[Test]
    public function a_vault_renamed_back_takes_the_readable_address_it_held_before(): void
    {
        $vault = Vault::factory()->create(['name' => 'Famille']);

        $vault->name = 'Amis';
        $vault->save();

        $vault->name = 'Famille';
        $vault->save();

        $this->assertSame('famille', $vault->fresh()->slug);
    }

    #[Test]
    public function it_reads_its_knowledge_folders_in_their_order(): void
    {
        $vault = Vault::factory()->create();
        $travel = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Travel', 'position' => 2]);
        $home = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Home', 'position' => 1]);
        KnowledgeFolder::factory()->create(['name' => 'Elsewhere']);

        $this->assertEquals([$home->id, $travel->id], $vault->knowledgeFolders()->pluck('id')->all());
    }

    #[Test]
    public function it_counts_the_people_holding_each_category_of_the_vault_in_the_order_of_the_list(): void
    {
        $vault = Vault::factory()->create();
        $list = ValueList::factory()->supplied(ValueListEnum::ContactCategories)->create(['account_id' => $vault->account_id]);
        $sales = $this->categoryOf($list, 'Sales', 1);
        $finerThings = $this->categoryOf($list, 'Finer Things Club', 2);
        $partyPlanning = ValueListItem::factory()->disabled()->create(['account_id' => $vault->account_id, 'value_list_id' => $list->id, 'position' => 3]);
        $scranton = $this->categoryOf($list, 'Scranton Strangler', 4);
        $this->categoryOf($list, 'Nobody holds this', 5);
        $field = Field::factory()->create(['type' => FieldTypeEnum::Multiselect, 'value_list_id' => $list->id]);
        $secondField = Field::factory()->create(['type' => FieldTypeEnum::Multiselect, 'value_list_id' => $list->id]);

        $jim = Contact::factory()->create(['vault_id' => $vault->id]);
        $this->hold($jim, $field, [$sales->id]);
        $this->hold($jim, $secondField, [$sales->id]);
        $this->hold(Contact::factory()->create(['vault_id' => $vault->id]), $field, [$finerThings->id, $sales->id]);
        $this->hold(Contact::factory()->create(['vault_id' => $vault->id]), $field, [$partyPlanning->id]);
        $this->hold(Contact::factory()->create(['vault_id' => $vault->id, 'is_archived' => true]), $field, [$scranton->id]);

        $otherVault = Vault::factory()->create(['account_id' => $vault->account_id]);
        $this->hold(Contact::factory()->create(['vault_id' => $otherVault->id]), $field, [$scranton->id]);

        $held = $vault->categoriesHeldByContacts(ContactGroupingEnum::Everyone, reachesSensitiveFields: true);

        $this->assertSame(
            [[$sales->id, 2], [$finerThings->id, 1], [$partyPlanning->id, 1]],
            array_map(fn (array $category): array => [$category['item']->id, $category['contacts']], $held),
        );
    }

    #[Test]
    public function it_offers_no_category_when_the_account_has_no_list_of_them(): void
    {
        $vault = Vault::factory()->create();

        $this->assertSame([], $vault->categoriesHeldByContacts(ContactGroupingEnum::Everyone, reachesSensitiveFields: true));
    }

    #[Test]
    public function it_leaves_out_what_a_sensitive_field_holds_for_a_caller_that_does_not_reach_sensitive_fields(): void
    {
        $vault = Vault::factory()->create();
        $list = ValueList::factory()->supplied(ValueListEnum::ContactCategories)->create(['account_id' => $vault->account_id]);
        $sales = $this->categoryOf($list, 'Sales', 1);
        $therapy = $this->categoryOf($list, 'In therapy', 2);
        $field = Field::factory()->create(['type' => FieldTypeEnum::Multiselect, 'value_list_id' => $list->id]);
        $sensitiveField = Field::factory()->create(['type' => FieldTypeEnum::Multiselect, 'value_list_id' => $list->id, 'is_sensitive' => true]);

        $jim = Contact::factory()->create(['vault_id' => $vault->id]);
        $this->hold($jim, $field, [$sales->id]);
        $this->hold($jim, $sensitiveField, [$sales->id, $therapy->id]);

        $count = fn (bool $reachesSensitiveFields): array => array_map(
            fn (array $category): array => [$category['item']->id, $category['contacts']],
            $vault->categoriesHeldByContacts(ContactGroupingEnum::Everyone, $reachesSensitiveFields),
        );

        $this->assertSame([[$sales->id, 1]], $count(false));
        $this->assertSame([[$sales->id, 1], [$therapy->id, 1]], $count(true));
    }

    #[Test]
    public function it_is_being_imported_into_while_an_import_writes_into_it_or_rolls_it_back(): void
    {
        $scranton = Vault::factory()->create(['name' => 'Scranton']);
        $chosen = AccountImport::factory()->create(['account_id' => $scranton->account_id, 'vault_id' => $scranton->id]);

        foreach ([AccountImportStatusEnum::Writing, AccountImportStatusEnum::RollingBack] as $status) {
            $chosen->update(['status' => $status]);

            $this->assertTrue($scranton->isBeingImportedInto());
        }

        $chosen->update(['status' => AccountImportStatusEnum::Completed]);
        AccountImport::factory()->create(['account_id' => $scranton->account_id, 'status' => AccountImportStatusEnum::Writing, 'created_vault_ids' => [$scranton->id]]);

        $this->assertTrue($scranton->isBeingImportedInto());
    }

    #[Test]
    public function it_is_not_being_imported_into_before_an_import_writes_or_once_it_has_ended(): void
    {
        $scranton = Vault::factory()->create(['name' => 'Scranton']);
        $import = AccountImport::factory()->create(['account_id' => $scranton->account_id, 'vault_id' => $scranton->id]);

        foreach ([AccountImportStatusEnum::Read, AccountImportStatusEnum::Completed, AccountImportStatusEnum::RolledBack, AccountImportStatusEnum::RollbackFailed] as $status) {
            $import->update(['status' => $status]);

            $this->assertFalse($scranton->isBeingImportedInto());
        }
    }

    #[Test]
    public function it_is_not_being_imported_into_while_an_import_writes_into_another_vault_of_the_account(): void
    {
        $scranton = Vault::factory()->create(['name' => 'Scranton']);
        $stamford = Vault::factory()->create(['name' => 'Stamford', 'account_id' => $scranton->account_id]);
        AccountImport::factory()->create(['account_id' => $scranton->account_id, 'status' => AccountImportStatusEnum::Writing, 'vault_id' => $scranton->id]);

        $this->assertFalse($stamford->isBeingImportedInto());
    }

    private function categoryOf(ValueList $list, string $label, int $position): ValueListItem
    {
        return ValueListItem::factory()->create([
            'account_id' => $list->account_id,
            'value_list_id' => $list->id,
            'label' => $label,
            'position' => $position,
        ]);
    }

    /** @param  list<int>  $itemIds */
    private function hold(Contact $contact, Field $field, array $itemIds): void
    {
        FieldValue::factory()->create([
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'value_string' => null,
            'value_list_item_ids' => $itemIds,
        ]);
    }

    #[Test]
    public function it_draws_the_four_newest_listed_contacts_of_each_vault_as_its_faces(): void
    {
        $user = User::factory()->create();
        $scranton = Vault::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $stamford = Vault::factory()->create(['account_id' => $user->account_id, 'position' => 2]);

        $listed = collect(['Michael', 'Dwight', 'Jim', 'Pam', 'Angela'])
            ->map(fn (string $name, int $index): Contact => Contact::factory()->create([
                'vault_id' => $scranton->id,
                'first_name' => $name,
                'created_at' => CarbonImmutable::parse('2026-01-01')->addDays($index),
            ]));
        Contact::factory()->partial()->create(['vault_id' => $scranton->id, 'created_at' => '2026-02-01']);
        Contact::factory()->archived()->create(['vault_id' => $scranton->id, 'created_at' => '2026-02-02']);
        $karen = Contact::factory()->create(['vault_id' => $stamford->id]);

        $vaults = Vault::query()->withCardValues($user)->orderBy('position')->get();

        $this->assertSame(
            [$listed[4]->id, $listed[3]->id, $listed[2]->id, $listed[1]->id],
            $vaults[0]->faces->pluck('id')->all(),
        );
        $this->assertSame([$karen->id], $vaults[1]->faces->pluck('id')->all());
    }

    #[Test]
    public function it_counts_the_reminders_awaiting_an_answer_until_the_end_of_the_month_of_the_reader(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-30 20:00:00', 'UTC'));

        $michael = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $michael->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $reminder = Reminder::factory()->create([
            'account_id' => $michael->account_id,
            'vault_id' => $vault->id,
            'remindable_type' => $contact->getMorphClass(),
            'remindable_id' => $contact->id,
            'is_active' => true,
        ]);

        foreach (['2026-10-12', '2026-11-30', '2026-12-15'] as $day) {
            ReminderOccurrence::factory()->for($reminder)->create([
                'local_date' => $day,
                'generation_key' => "1:{$day}",
                'scheduled_for' => CarbonImmutable::parse($day),
            ]);
        }
        ReminderOccurrence::factory()->for($reminder)->remembered()->create([
            'local_date' => '2026-11-02',
            'generation_key' => '1:2026-11-02',
            'scheduled_for' => CarbonImmutable::parse('2026-11-02'),
        ]);

        $inScranton = Vault::query()->withCardValues($michael)->findOrFail($vault->id);

        $michael->timezone = 'Pacific/Kiritimati';
        $inKiritimati = Vault::query()->withCardValues($michael)->findOrFail($vault->id);

        $this->assertSame(2, $inScranton->upcoming_reminder_count);
        $this->assertSame(3, $inKiritimati->upcoming_reminder_count);
    }

    #[Test]
    public function it_orders_vaults_by_their_latest_visit_then_the_ones_never_opened_by_position(): void
    {
        $user = User::factory()->create();
        $neverOpenedFirst = Vault::factory()->create(['account_id' => $user->account_id, 'position' => 1, 'last_visited_at' => null]);
        $openedLongAgo = Vault::factory()->create(['account_id' => $user->account_id, 'position' => 2, 'last_visited_at' => '2026-01-01 10:00:00']);
        $neverOpenedSecond = Vault::factory()->create(['account_id' => $user->account_id, 'position' => 3, 'last_visited_at' => null]);
        $openedYesterday = Vault::factory()->create(['account_id' => $user->account_id, 'position' => 4, 'last_visited_at' => now()->subDay()]);

        $this->assertSame(
            [$openedYesterday->id, $openedLongAgo->id, $neverOpenedFirst->id, $neverOpenedSecond->id],
            Vault::query()->where('account_id', $user->account_id)->orderedByRecentVisit()->pluck('id')->all(),
        );
    }
}
