<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\File;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactMergeViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactMergeViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_every_other_contact_of_the_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Jim', 'last_name' => 'Halpert']);
        $pam = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Pam', 'last_name' => 'Beesly']);

        $candidates = new ContactMergeViewModel(user: $user, vault: $vault, contact: $jim)->candidates();

        $this->assertCount(1, $candidates);
        $this->assertEquals($pam->uuid, $candidates[0]['uuid']);
    }

    #[Test]
    public function it_never_offers_the_contact_representing_the_user(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Jim', 'last_name' => 'Halpert']);
        Contact::factory()->create(['vault_id' => $vault->id, 'is_me' => true]);

        $candidates = new ContactMergeViewModel(user: $user, vault: $vault, contact: $jim)->candidates();

        $this->assertSame([], $candidates);
    }

    #[Test]
    public function it_offers_nothing_from_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Jim', 'last_name' => 'Halpert']);
        Contact::factory()->create(['vault_id' => $elsewhere->id]);

        $candidates = new ContactMergeViewModel(user: $user, vault: $vault, contact: $jim)->candidates();

        $this->assertSame([], $candidates);
    }

    #[Test]
    public function it_reads_the_preview_from_the_action(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Jim', 'last_name' => 'Halpert']);
        $james = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'James', 'last_name' => 'Halpert']);

        $preview = new ContactMergeViewModel(user: $user, vault: $vault, contact: $jim, other: $james)->preview();

        $this->assertEquals($jim->uuid, $preview['kept']['uuid']);
        $this->assertArrayHasKey('fingerprint', $preview);
    }

    #[Test]
    public function it_has_no_preview_until_somebody_is_chosen(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Jim', 'last_name' => 'Halpert']);

        $this->assertSame([], new ContactMergeViewModel(user: $user, vault: $vault, contact: $jim)->preview());
    }

    #[Test]
    public function it_says_how_many_of_each_thing_would_come_over(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Jim', 'last_name' => 'Halpert']);

        $viewModel = new ContactMergeViewModel(user: $user, vault: $vault, contact: $jim);

        $this->assertEquals('1 answer comes over', $viewModel->movedLine(['kind' => 'field_value', 'count' => 1]));
        $this->assertEquals('4 answers come over', $viewModel->movedLine(['kind' => 'field_value', 'count' => 4]));
        $this->assertEquals('0 answers come over', $viewModel->movedLine(['kind' => 'field_value', 'count' => 0]));
        $this->assertEquals('2 reminders come over', $viewModel->movedLine(['kind' => 'reminder', 'count' => 2]));
    }

    #[Test]
    public function it_offers_the_way_into_both_records_with_the_one_that_stays_first(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Jim', 'last_name' => 'Halpert']);
        $james = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'James', 'last_name' => 'Halpert']);

        $records = new ContactMergeViewModel(user: $user, vault: $vault, contact: $jim, other: $james)->records();

        $this->assertCount(2, $records);
        $this->assertEquals('Jim Halpert', $records[0]['name']);
        $this->assertEquals(route('contacts.show', ['vault' => $vault, 'contact' => $jim]), $records[0]['url']);
        $this->assertEquals('James Halpert', $records[1]['name']);
        $this->assertEquals(route('contacts.show', ['vault' => $vault, 'contact' => $james]), $records[1]['url']);
    }

    #[Test]
    public function it_says_where_the_picture_of_each_record_is_read_from(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Jim', 'last_name' => 'Halpert']);
        $james = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'James', 'last_name' => 'Halpert']);

        $file = File::factory()->image()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $james->avatar_file_id = $file->id;
        $james->save();

        $records = new ContactMergeViewModel(user: $user, vault: $vault, contact: $jim, other: $james->refresh())->records();

        $this->assertNull($records[0]['avatarUrl']);
        $this->assertEquals(route('files.variants.show', ['vault' => $vault, 'file' => $file, 'size' => Contact::AVATAR_THUMBNAIL_PIXELS]), $records[1]['avatarUrl']);
    }

    #[Test]
    public function it_names_no_record_while_nobody_is_picked(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Jim', 'last_name' => 'Halpert']);

        $this->assertSame([], new ContactMergeViewModel(user: $user, vault: $vault, contact: $jim)->records());
    }

    #[Test]
    public function it_names_both_contacts_in_its_title(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Jim', 'last_name' => 'Halpert']);
        $james = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'James', 'last_name' => 'Halpert']);

        $viewModel = new ContactMergeViewModel(user: $user, vault: $vault, contact: $jim, other: $james);

        $this->assertStringContainsString('Jim Halpert', $viewModel->title());
        $this->assertStringContainsString('James Halpert', $viewModel->title());
    }
}
