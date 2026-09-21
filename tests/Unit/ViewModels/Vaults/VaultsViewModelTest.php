<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\ReminderOccurrenceStatusEnum;
use App\Models\Contact;
use App\Models\File;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\VaultsViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_the_vault_you_were_last_in_first(): void
    {
        $user = User::factory()->create();

        Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Travail',
            'position' => 1,
            'last_visited_at' => now()->subWeek(),
        ]);

        Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Famille',
            'position' => 2,
            'last_visited_at' => now()->subHour(),
        ]);

        $vaults = new VaultsViewModel(user: $user)->vaults();

        $this->assertSame(['Famille', 'Travail'], array_column($vaults, 'name'));
    }

    #[Test]
    public function a_vault_never_opened_falls_in_behind_the_ones_that_were(): void
    {
        $user = User::factory()->create();

        Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Le club',
            'position' => 1,
            'last_visited_at' => null,
        ]);

        Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Famille',
            'position' => 2,
            'last_visited_at' => now()->subDay(),
        ]);

        $this->assertSame(
            ['Famille', 'Le club'],
            array_column(new VaultsViewModel(user: $user)->vaults(), 'name'),
        );
    }

    #[Test]
    public function a_vault_says_when_it_was_last_opened(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Le club',
            'position' => 1,
            'last_visited_at' => null,
        ]);

        Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Famille',
            'position' => 2,
            'last_visited_at' => now()->subHours(3),
        ]);

        $lines = array_combine(
            array_column($vaults = new VaultsViewModel(user: $user)->vaults(), 'name'),
            array_column($vaults, 'visitLine'),
        );

        $this->assertSame('Not opened yet', $lines['Le club']);
        $this->assertSame('Opened 3 hours ago', $lines['Famille']);
    }

    #[Test]
    public function the_ones_never_opened_keep_the_order_they_were_given(): void
    {
        $user = User::factory()->create();

        Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'First', 'position' => 1, 'last_visited_at' => null]);
        Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Second', 'position' => 2, 'last_visited_at' => null]);

        $this->assertSame(
            ['First', 'Second'],
            array_column(new VaultsViewModel(user: $user)->vaults(), 'name'),
        );
    }

    #[Test]
    public function it_shows_four_faces_and_says_how_many_it_left_out(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 18]);

        Contact::factory()->count(6)->create(['vault_id' => $vault->id]);

        $card = new VaultsViewModel(user: $user)->vaults()[0];

        $this->assertCount(4, $card['faces']);
        $this->assertSame('and 14 more', $card['moreFaces']);
    }

    #[Test]
    public function it_leaves_nobody_out_when_the_vault_holds_no_more_than_four(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 2]);

        Contact::factory()->count(2)->create(['vault_id' => $vault->id]);

        $card = new VaultsViewModel(user: $user)->vaults()[0];

        $this->assertCount(2, $card['faces']);
        $this->assertNull($card['moreFaces']);
    }

    #[Test]
    public function a_face_carries_the_picture_of_whoever_has_one(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 1]);

        $file = File::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        Contact::factory()->create([
            'vault_id' => $vault->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'avatar_file_id' => $file->id,
        ]);

        $face = new VaultsViewModel(user: $user)->vaults()[0]['faces'][0];

        $this->assertSame('Michael Scott', $face['name']);
        $this->assertSame(route('files.show', ['vault' => $vault, 'file' => $file]), $face['avatarUrl']);
    }

    #[Test]
    public function it_counts_the_dates_the_vault_owes_before_the_month_is_out(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $reminder = Reminder::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'remindable_type' => $contact->getMorphClass(),
            'remindable_id' => $contact->id,
            'is_active' => true,
        ]);

        $thisMonth = CarbonImmutable::now()->startOfMonth()->addDay();

        foreach ([$thisMonth, $thisMonth->addDay(), CarbonImmutable::now()->endOfMonth()->addMonth()] as $when) {
            ReminderOccurrence::factory()->create([
                'account_id' => $user->account_id,
                'vault_id' => $vault->id,
                'reminder_id' => $reminder->id,
                'status' => ReminderOccurrenceStatusEnum::Pending,
                'source_local_date' => $when->toDateString(),
                'generation_key' => "1:{$when->toDateString()}:on::",
                'scheduled_for' => $when,
                'local_date' => $when->toDateString(),
            ]);
        }

        $this->assertSame(2, new VaultsViewModel(user: $user)->vaults()[0]['dateCount']);
    }

    #[Test]
    public function no_task_is_ever_open_while_the_application_writes_none_down(): void
    {
        $user = User::factory()->create();

        Vault::factory()->create(['account_id' => $user->account_id]);

        $this->assertSame(0, new VaultsViewModel(user: $user)->vaults()[0]['openTaskCount']);
    }

    #[Test]
    public function the_mark_on_a_card_is_the_colour_somebody_chose_for_the_vault(): void
    {
        $user = User::factory()->create();

        Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Famille',
            'colour' => '#2f7350',
        ]);

        $vault = new VaultsViewModel(user: $user)->vaults()[0];

        $this->assertSame('#2f7350', $vault['colour']);
        $this->assertSame('color-mix(in srgb, #2f7350 12%, var(--canvas))', $vault['tint']);
        $this->assertSame('F', $vault['initial']);
    }

    #[Test]
    public function it_only_reaches_the_vaults_of_the_account(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();

        Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Famille']);
        Vault::factory()->create(['account_id' => $stranger->account_id, 'name' => 'Sabre']);

        $this->assertSame(
            ['Famille'],
            array_column(new VaultsViewModel(user: $user)->vaults(), 'name'),
        );
    }
}
