<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use App\Models\Contact;
use App\Models\EntityType;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SidebarTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_vault_column_offers_the_destinations_of_that_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin']);

        $rendered = $this->actingAs($user)->blade(
            '<x-vault.sidebar :user="$user" :vault="$vault" current="dashboard" />',
            ['user' => $user, 'vault' => $vault],
        );

        $rendered->assertSee(route('vaults.show', $vault), escape: false);
        $rendered->assertSee(route('contacts.index', $vault), escape: false);
        $rendered->assertSee(route('vaults.settings.index', $vault), escape: false);
        $rendered->assertSee('Dashboard');
        $rendered->assertSee('People');
        $rendered->assertSee('Vault settings');
    }

    #[Test]
    public function the_vault_column_offers_search_with_the_command_key_on_an_apple_device(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)
            ->withHeader('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 15_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0 Safari/605.1.15')
            ->get(route('vaults.show', $vault));

        $response->assertSee(route('search.index', $vault), escape: false);
        $response->assertSee('x-data="searchShortcut(', escape: false);
        $response->assertSee('aria-keyshortcuts="Meta+K"', escape: false);
        $response->assertSee('⌘K');
    }

    #[Test]
    public function the_vault_column_offers_search_with_the_control_key_elsewhere(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)
            ->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36')
            ->get(route('vaults.show', $vault));

        $response->assertSee('aria-keyshortcuts="Control+K"', escape: false);
        $response->assertSee('Ctrl K');
        $response->assertDontSee('⌘K');
    }

    #[Test]
    public function the_vault_column_lists_the_types_of_the_vault_under_people_in_their_order(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $this->visibleType($vault, 'Animals', 2);
        $this->visibleType($vault, 'Companies', 1);

        $this->actingAs($user)->blade(
            '<x-vault.sidebar :user="$user" :vault="$vault" current="dashboard" />',
            ['user' => $user, 'vault' => $vault],
        )->assertSeeInOrder(['People', 'Companies', 'Animals', 'Reminders']);
    }

    #[Test]
    public function the_vault_column_marks_the_type_being_read_as_the_current_page(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = $this->visibleType($vault, 'Animals', 1);

        $this->actingAs($user)->blade(
            '<x-vault.sidebar :user="$user" :vault="$vault" :current="$current" />',
            ['user' => $user, 'vault' => $vault, 'current' => "entity-type-{$type->slug}"],
        )->assertSeeInOrder([
            'href="'.route('entities.index', ['vault' => $vault, 'entityType' => $type]).'"',
            'aria-current="page"',
            'Animals',
        ], escape: false);
    }

    #[Test]
    public function the_vault_column_says_where_you_are_with_more_than_a_colour(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->blade(
            '<x-vault.sidebar :user="$user" :vault="$vault" current="contacts" />',
            ['user' => $user, 'vault' => $vault],
        )->assertSee('aria-current="page"', escape: false);
    }

    #[Test]
    public function the_vault_column_does_not_offer_tasks_or_files(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $rendered = $this->actingAs($user)->blade(
            '<x-vault.sidebar :user="$user" :vault="$vault" />',
            ['user' => $user, 'vault' => $vault],
        );

        $rendered->assertSee('Reminders');
        $rendered->assertDontSee('Tasks');
        $rendered->assertDontSee('Files');
        $rendered->assertDontSee('Journal');
    }

    #[Test]
    public function the_name_of_the_application_at_the_top_of_either_column_leads_to_the_vaults(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->blade(
            '<x-vault.sidebar :user="$user" :vault="$vault" current="dashboard" />',
            ['user' => $user, 'vault' => $vault],
        )->assertSee('href="'.route('vaults.index').'"', escape: false);

        $this->actingAs($user)->blade(
            '<x-settings.sidebar :user="$user" current="profile" />',
            ['user' => $user],
        )->assertSee('href="'.route('vaults.index').'"', escape: false);
    }

    #[Test]
    public function the_settings_column_offers_the_settings_and_the_way_out_of_them(): void
    {
        $user = User::factory()->create();

        $rendered = $this->actingAs($user)->blade(
            '<x-settings.sidebar :user="$user" current="profile" />',
            ['user' => $user],
        );

        $rendered->assertSee(route('settings.profile.index'), escape: false);
        $rendered->assertSee(route('settings.templates.index'), escape: false);
        $rendered->assertSee(route('vaults.index'), escape: false);
        $rendered->assertSee('Leave the settings');
        $rendered->assertSee('aria-current="page"', escape: false);
    }

    #[Test]
    public function the_settings_column_puts_every_destination_in_its_group_and_its_order(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->blade(
            '<x-settings.sidebar :user="$user" current="entity-types" />',
            ['user' => $user],
        )->assertSeeInOrder([
            'Profile',
            'Security and access',
            'Preferences',
            'Logs',
            'Personalisation',
            'Contacts and more',
            'Templates',
            'Value lists',
            'Account',
            'Billing',
        ]);
    }

    #[Test]
    public function both_columns_carry_who_is_signed_in_and_the_way_out(): void
    {
        $user = User::factory()->create(['first_name' => 'Pam', 'last_name' => 'Beesly']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        foreach ([
            ['<x-vault.sidebar :user="$user" :vault="$vault" />', ['user' => $user, 'vault' => $vault]],
            ['<x-settings.sidebar :user="$user" />', ['user' => $user]],
        ] as [$template, $data]) {
            $rendered = $this->actingAs($user)->blade($template, $data);

            $rendered->assertSee('Pam Beesly');
            $rendered->assertSee('id="shell-avatar"', escape: false);
            $rendered->assertSee('aria-haspopup="menu"', escape: false);
            $rendered->assertSee(route('settings.index'), escape: false);
            $rendered->assertSee('Account settings');
            $rendered->assertSee('Sign out');
        }
    }

    #[Test]
    public function the_way_into_the_settings_and_the_way_out_are_kept_behind_the_name(): void
    {
        $user = User::factory()->create(['first_name' => 'Pam', 'last_name' => 'Beesly']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $rendered = $this->actingAs($user)->blade(
            '<x-vault.sidebar :user="$user" :vault="$vault" />',
            ['user' => $user, 'vault' => $vault],
        );

        $rendered->assertSee('role="menu"', escape: false);
        $rendered->assertSee('x-show="open"', escape: false);
        $rendered->assertSee(route('auth.login.destroy'), escape: false);
    }

    #[Test]
    public function both_columns_are_the_drawer_the_bar_opens(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        foreach ([
            ['<x-vault.sidebar :user="$user" :vault="$vault" />', ['user' => $user, 'vault' => $vault]],
            ['<x-settings.sidebar :user="$user" />', ['user' => $user]],
        ] as [$template, $data]) {
            $rendered = $this->actingAs($user)->blade($template, $data);

            $rendered->assertSee('id="main-nav"', escape: false);
            $rendered->assertSee('Close the menu');
        }
    }

    #[Test]
    public function the_vault_column_offers_the_reminders_and_counts_what_awaits_an_answer(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->reminderAbout(user: $user, contact: Contact::factory()->create(['vault_id' => $vault->id]));

        $this->deadline($rule, -3);
        $this->deadline($rule, 0);
        $this->deadline($rule, 9);
        $this->deadline($rule, -30)->forceFill(['answer' => 'remembered', 'answered_at' => now()])->save();

        $rendered = $this->actingAs($user)->blade(
            '<x-vault.sidebar :user="$user" :vault="$vault" current="reminders" />',
            ['user' => $user, 'vault' => $vault],
        );

        $rendered->assertSee(route('reminders.index', $vault), escape: false);
        $rendered->assertSee('Reminders');
        $rendered->assertSee('2');
        $rendered->assertDontSee('>3<', escape: false);
    }

    private function deadline(Reminder $rule, int $days): ReminderOccurrence
    {
        $day = CarbonImmutable::now($rule->timezone)->addDays($days)->startOfDay();

        return ReminderOccurrence::factory()->for($rule)->create([
            'generation_key' => '1:'.$day->toDateString(),
            'scheduled_for' => $day->setTime(9, 0)->utc(),
            'local_date' => $day->toDateString(),
        ]);
    }

    private function visibleType(Vault $vault, string $plural, int $position): EntityType
    {
        $type = EntityType::factory()->create([
            'account_id' => $vault->account_id,
            'name_plural' => $plural,
            'position' => $position,
        ]);
        VaultDefaultTemplate::factory()->forEntity($type)->create(['vault_id' => $vault->id]);

        return $type;
    }
}
