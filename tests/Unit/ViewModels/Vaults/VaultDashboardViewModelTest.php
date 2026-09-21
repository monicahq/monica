<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\File;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use App\ViewModels\Vaults\VaultDashboardViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultDashboardViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_what_the_vault_is_called_and_what_it_is_for(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'description' => 'Everybody at the Scranton branch',
        ]);

        $viewModel = new VaultDashboardViewModel(user: $user, vault: $vault);

        $this->assertEquals('Dunder Mifflin', $viewModel->title());
        $this->assertEquals('Everybody at the Scranton branch', $viewModel->description());
        $this->assertTrue($vault->is($viewModel->vault()));
    }

    #[Test]
    public function it_says_how_many_people_are_in_it(): void
    {
        $user = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 148]);

        $viewModel = new VaultDashboardViewModel(user: $user, vault: $vault);

        $this->assertEquals('148 people', $viewModel->peopleCaption());
    }

    #[Test]
    public function it_counts_one_person_and_none_the_way_somebody_would_say_it(): void
    {
        $user = User::factory()->create();
        $empty = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 0]);
        $alone = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 1]);

        $this->assertEquals('Nobody in it yet', new VaultDashboardViewModel(user: $user, vault: $empty)->peopleCaption());
        $this->assertEquals('1 person', new VaultDashboardViewModel(user: $user, vault: $alone)->peopleCaption());
    }

    #[Test]
    public function it_shows_the_last_people_written_down(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        Contact::factory()->create([
            'vault_id' => $vault->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'created_at' => now()->subYear(),
        ]);

        Contact::factory()->create([
            'vault_id' => $vault->id,
            'first_name' => 'Pam',
            'last_name' => 'Beesly',
            'nickname' => 'Pammy',
            'created_at' => now()->subDay(),
        ]);

        $people = new VaultDashboardViewModel(user: $user, vault: $vault)->people();

        $this->assertEquals(['Pam Beesly', 'Michael Scott'], array_column($people, 'name'));
        $this->assertEquals('Pammy', $people[0]['nickname']);
        $this->assertEquals('', $people[1]['nickname']);
    }

    #[Test]
    public function it_says_where_the_picture_of_somebody_is_read_from(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $file = File::factory()->image()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
        ]);

        Contact::factory()->create(['vault_id' => $vault->id, 'avatar_file_id' => $file->id]);
        Contact::factory()->create(['vault_id' => $vault->id, 'created_at' => now()->subYear()]);

        $people = new VaultDashboardViewModel(user: $user, vault: $vault)->people();

        $this->assertEquals(
            route('files.show', ['vault' => $vault, 'file' => $file]),
            $people[0]['avatarUrl'],
        );

        $this->assertNull($people[1]['avatarUrl']);
    }

    #[Test]
    public function it_shows_only_a_handful_of_people_and_points_at_the_rest(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 12]);
        Contact::factory()->count(8)->create(['vault_id' => $vault->id]);

        $viewModel = new VaultDashboardViewModel(user: $user, vault: $vault);

        $this->assertCount(5, $viewModel->people());
        $this->assertEquals('All 12 people', $viewModel->allPeopleLabel());
    }

    #[Test]
    public function it_says_the_list_is_whole_when_everybody_in_the_vault_is_on_it(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 3]);

        $this->assertEquals('The whole list', new VaultDashboardViewModel(user: $user, vault: $vault)->allPeopleLabel());
    }

    #[Test]
    public function it_leaves_out_the_people_the_lists_do_not_show(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Jim', 'last_name' => 'Halpert']);
        Contact::factory()->archived()->create(['vault_id' => $vault->id, 'first_name' => 'Roy', 'last_name' => 'Anderson']);
        Contact::factory()->partial()->create(['vault_id' => $vault->id, 'first_name' => 'Todd']);

        $people = new VaultDashboardViewModel(user: $user, vault: $vault)->people();

        $this->assertEquals(['Jim Halpert'], array_column($people, 'name'));
    }

    #[Test]
    public function it_leaves_out_the_people_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $other = Vault::factory()->create(['account_id' => $user->account_id]);

        Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Dwight', 'last_name' => 'Schrute']);
        Contact::factory()->create(['vault_id' => $other->id, 'first_name' => 'Mose', 'last_name' => 'Schrute']);

        $people = new VaultDashboardViewModel(user: $user, vault: $vault)->people();

        $this->assertEquals(['Dwight Schrute'], array_column($people, 'name'));
    }

    #[Test]
    public function it_names_the_template_a_new_contact_starts_from(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 148]);

        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Colleague']);
        $version = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        VaultDefaultTemplate::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->id,
        ]);

        $rows = new VaultDashboardViewModel(user: $user, vault: $vault)->settingsRows();

        $this->assertEquals('What a new contact starts from', $rows[0]['title']);
        $this->assertEquals('Colleague', $rows[0]['line']);
        $this->assertEquals('Delete this vault', $rows[1]['title']);
        $this->assertEquals('Deletes 148 people and everything written about them.', $rows[1]['line']);
    }

    #[Test]
    public function it_says_what_deleting_an_empty_vault_would_take(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 0]);
        $alone = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 1]);

        $this->assertEquals(
            'Takes the vault and everything in it.',
            new VaultDashboardViewModel(user: $user, vault: $vault)->settingsRows()[1]['line'],
        );

        $this->assertEquals(
            'Deletes the one person in it and everything written about them.',
            new VaultDashboardViewModel(user: $user, vault: $alone)->settingsRows()[1]['line'],
        );
    }

    #[Test]
    public function it_says_a_vault_without_a_default_leaves_the_template_to_whoever_writes(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $rows = new VaultDashboardViewModel(user: $user, vault: $vault)->settingsRows();

        $this->assertEquals('Nothing is set, so whoever writes somebody down picks a template themselves.', $rows[0]['line']);
    }

    #[Test]
    public function it_draws_the_parts_of_a_vault_that_have_no_model_yet(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $sections = new VaultDashboardViewModel(user: $user, vault: $vault)->unbuiltSections();

        $this->assertEquals(['Tasks', 'Reminders', 'Files'], array_column($sections, 'title'));
        $this->assertEquals(['tasks', 'reminder', 'file'], array_column($sections, 'icon'));
    }

    #[Test]
    public function it_says_where_every_box_leads(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $viewModel = new VaultDashboardViewModel(user: $user, vault: $vault);

        $this->assertEquals(route('vaults.index'), $viewModel->vaultsUrl());
        $this->assertEquals(route('contacts.new', $vault), $viewModel->newContactUrl());
        $this->assertEquals(route('contacts.index', $vault), $viewModel->allContactsUrl());
        $this->assertEquals(route('vaults.settings.index', $vault), $viewModel->settingsUrl());
    }
}
