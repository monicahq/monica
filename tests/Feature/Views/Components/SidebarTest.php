<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use App\Models\Contact;
use App\Models\EntityType;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeNote;
use App\Models\PossessionType;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SidebarTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_vault_column_is_a_drawer_that_holds_focus_while_open(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $rendered = $this->actingAs($user)->blade(
            '<x-vault.sidebar :user="$user" :vault="$vault" current="dashboard" />',
            ['user' => $user, 'vault' => $vault],
        );

        $rendered->assertSee('<aside', escape: false);
        $rendered->assertSee('id="main-nav"', escape: false);
        $rendered->assertSee('x-trap.noscroll.inert="navOpen"', escape: false);
    }

    #[Test]
    public function the_vault_column_leaves_out_the_groups_while_they_are_off_for_the_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $user->account->features()->deactivate('groups');
        Feature::flushCache();

        $rendered = $this->actingAs($user)->blade(
            '<x-vault.sidebar :user="$user" :vault="$vault" current="dashboard" />',
            ['user' => $user, 'vault' => $vault],
        );

        $rendered->assertDontSee('Groups');
        $rendered->assertDontSee(route('groups.index', $vault), escape: false);
    }

    #[Test]
    public function the_vault_column_leaves_out_every_section_the_vault_hides(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'shows_people' => false,
            'shows_things' => false,
            'shows_knowledge' => false,
        ]);
        PossessionType::factory()->create(['account_id' => $user->account_id]);

        $rendered = $this->actingAs($user)->blade(
            '<x-vault.sidebar :user="$user" :vault="$vault" current="dashboard" />',
            ['user' => $user, 'vault' => $vault],
        );

        $rendered->assertDontSee(route('contacts.index', $vault), escape: false);
        $rendered->assertDontSee(route('groups.index', $vault), escape: false);
        $rendered->assertDontSee(route('possessions.index', $vault), escape: false);
        $rendered->assertDontSee(route('knowledge.index', $vault), escape: false);
        $rendered->assertSee(route('reminders.index', $vault), escape: false);
        $rendered->assertSee(route('vaults.settings.index', $vault), escape: false);
    }

    #[Test]
    public function the_vault_column_shows_the_sections_another_vault_hides(): void
    {
        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id, 'shows_people' => false]);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        PossessionType::factory()->create(['account_id' => $user->account_id]);

        $rendered = $this->actingAs($user)->blade(
            '<x-vault.sidebar :user="$user" :vault="$vault" current="dashboard" />',
            ['user' => $user, 'vault' => $vault],
        );

        $rendered->assertSee(route('contacts.index', $vault), escape: false);
        $rendered->assertSee(route('possessions.index', $vault), escape: false);
        $rendered->assertSee(route('knowledge.index', $vault), escape: false);
    }

    #[Test]
    public function the_vault_column_offers_search_with_the_command_key_on_an_apple_device(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)
            ->withHeader('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 15_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0 Safari/605.1.15')
            ->get(route('vaults.show', $vault));

        $response->assertSee('href="'.route('search.index', $vault).'#query"', escape: false);
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
        $response->assertDontSee('⌘K');
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
        $rendered->assertSee('aria-current="page"', escape: false);
    }

    #[Test]
    public function neither_column_carries_the_account_or_the_theme_which_the_bar_holds(): void
    {
        $user = User::factory()->create(['first_name' => 'Pam', 'last_name' => 'Beesly']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        foreach ([
            ['<x-vault.sidebar :user="$user" :vault="$vault" />', ['user' => $user, 'vault' => $vault]],
            ['<x-settings.sidebar :user="$user" />', ['user' => $user]],
        ] as [$template, $data]) {
            $rendered = $this->actingAs($user)->blade($template, $data);

            $rendered->assertDontSee('Pam Beesly');
            $rendered->assertDontSee(route('auth.login.destroy'), escape: false);
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
        $rendered->assertSee('2');
        $rendered->assertDontSee('>3<', escape: false);
    }

    #[Test]
    public function the_vault_column_offers_knowledge_and_counts_its_notes_and_lists(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);
        KnowledgeNote::factory()->count(2)->create(['knowledge_folder_id' => $folder->id]);
        KnowledgeList::factory()->create(['knowledge_folder_id' => $folder->id]);
        KnowledgeNote::factory()->create();

        $rendered = $this->actingAs($user)->blade(
            '<x-vault.sidebar :user="$user" :vault="$vault" current="knowledge" />',
            ['user' => $user, 'vault' => $vault],
        );

        $rendered->assertSeeInOrder([
            'href="'.route('knowledge.index', $vault).'"',
            'aria-current="page"',
        ], escape: false);
        $this->assertMatchesRegularExpression('/Knowledge\s+3\s/', strip_tags((string) $rendered));
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
        $this->entityDefaultCarrying($vault, $type, []);

        return $type;
    }
}
