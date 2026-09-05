<?php

declare(strict_types=1);

namespace Tests\Unit\Seeders;

use App\Enums\ContactCategoryEnum;
use App\Enums\DefaultFieldEnum;
use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\ValueListItem;
use App\Models\Vault;
use Carbon\Carbon;
use Database\Seeders\ExampleAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExampleAccountSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_monica_in_as_the_only_user_of_the_account(): void
    {
        new ExampleAccountSeeder()->run();

        $this->assertCount(1, User::all());
        $this->assertDatabaseHas('users', [
            'first_name' => 'Monica',
            'last_name' => 'Geller',
            'email' => ExampleAccountSeeder::EMAIL,
        ]);
    }

    #[Test]
    public function it_confirms_her_address_so_the_account_can_be_used(): void
    {
        new ExampleAccountSeeder()->run();

        $this->assertNotNull(User::query()->firstOrFail()->email_verified_at);
    }

    #[Test]
    public function it_writes_her_down_as_the_me_contact_of_the_vault(): void
    {
        new ExampleAccountSeeder()->run();

        $vault = Vault::query()->firstOrFail();
        $me = Contact::query()->where('vault_id', $vault->id)->where('is_me', true)->firstOrFail();

        $this->assertEquals('Monica', $me->first_name);
        $this->assertEquals('Geller', $me->last_name);
        $this->assertCount(1, Contact::query()->where('is_me', true)->get());
    }

    #[Test]
    public function it_fills_the_vault_with_the_rest_of_the_cast(): void
    {
        new ExampleAccountSeeder()->run();

        $this->assertGreaterThanOrEqual(15, Contact::query()->count());

        foreach (['Rachel', 'Ross', 'Chandler', 'Joey', 'Phoebe'] as $firstName) {
            $this->assertDatabaseHas('contacts', ['first_name' => $firstName]);
        }
    }

    #[Test]
    public function it_writes_the_values_the_show_gives_a_person(): void
    {
        new ExampleAccountSeeder()->run();

        $joey = Contact::query()->where('first_name', 'Joey')->firstOrFail();

        $this->assertDatabaseHas('field_values', [
            'valuable_id' => $joey->id,
            'field_key' => DefaultFieldEnum::Birthday->value,
            'value_date' => '1968-01-09 00:00:00',
        ]);
        $this->assertDatabaseHas('field_values', [
            'valuable_id' => $joey->id,
            'field_key' => DefaultFieldEnum::Phone->value,
            'value_string' => '+1 212 555 0109',
        ]);
        $this->assertStringContainsString(
            'Drake Ramoray',
            (string) FieldValue::query()
                ->where('valuable_id', $joey->id)
                ->where('field_key', DefaultFieldEnum::Note->value)
                ->value('value_text'),
        );
    }

    #[Test]
    public function it_files_a_person_under_the_category_they_belong_to(): void
    {
        new ExampleAccountSeeder()->run();

        $ross = Contact::query()->where('first_name', 'Ross')->firstOrFail();

        $category = FieldValue::query()
            ->where('valuable_id', $ross->id)
            ->where('field_key', DefaultFieldEnum::Category->value)
            ->firstOrFail();

        $family = ValueListItem::query()->where('key', ContactCategoryEnum::Family->value)->firstOrFail();

        $this->assertEquals([$family->id], $category->value_list_item_ids);
    }

    #[Test]
    public function it_leaves_the_birthday_empty_for_somebody_the_show_never_dates(): void
    {
        new ExampleAccountSeeder()->run();

        $gunther = Contact::query()->where('first_name', 'Gunther')->firstOrFail();

        $this->assertDatabaseMissing('field_values', [
            'valuable_id' => $gunther->id,
            'field_key' => DefaultFieldEnum::Birthday->value,
        ]);
        $this->assertFalse(Reminder::query()->where('remindable_id', $gunther->id)->exists());
    }

    #[Test]
    public function it_marks_a_few_of_them_favourite(): void
    {
        new ExampleAccountSeeder()->run();

        $favourites = Contact::query()->where('is_favourite', true)->pluck('first_name');

        $this->assertCount(3, $favourites);
        $this->assertContains('Rachel', $favourites);
        $this->assertContains('Ross', $favourites);
        $this->assertContains('Chandler', $favourites);
    }

    #[Test]
    public function it_archives_the_neighbour_who_died(): void
    {
        new ExampleAccountSeeder()->run();

        $heckles = Contact::query()->where('last_name', 'Heckles')->firstOrFail();

        $this->assertTrue($heckles->is_archived);
    }

    #[Test]
    public function it_sets_a_reminder_on_every_birthday_but_monicas_own(): void
    {
        new ExampleAccountSeeder()->run();

        $birthdays = FieldValue::query()->where('field_key', DefaultFieldEnum::Birthday->value)->count();
        $monica = Contact::query()->where('is_me', true)->firstOrFail();

        $this->assertEquals($birthdays - 1, Reminder::query()->count());
        $this->assertGreaterThanOrEqual(5, Reminder::query()->count());
        $this->assertEquals(Reminder::query()->count(), Reminder::query()->where('is_active', true)->count());
        $this->assertFalse(Reminder::query()->where('remindable_id', $monica->id)->exists());
    }

    #[Test]
    public function it_varies_what_the_reminders_say_and_when_they_warn(): void
    {
        new ExampleAccountSeeder()->run();

        $reminders = Reminder::query()->get();

        $this->assertEquals($reminders->count(), $reminders->pluck('name')->unique()->count());
        $this->assertGreaterThan(1, $reminders->pluck('moments')->map(fn (array $moments): string => json_encode($moments))->unique()->count());
    }

    #[Test]
    #[DataProvider('monthsToSeedIn')]
    public function it_has_something_due_whatever_month_it_is_seeded_in(string $today): void
    {
        Carbon::setTestNow(Carbon::parse($today));

        new ExampleAccountSeeder()->run();

        $this->assertTrue(ReminderOccurrence::query()->exists());
    }

    /** @return array<string, array<int, string>> */
    public static function monthsToSeedIn(): array
    {
        return [
            'late spring' => ['2027-05-20'],
            'early summer' => ['2027-06-15'],
            'high summer' => ['2027-07-15'],
            'late summer' => ['2027-08-15'],
            'early autumn' => ['2027-09-15'],
            'winter' => ['2027-12-01'],
        ];
    }

    #[Test]
    public function it_seeds_with_nothing_working_the_queue(): void
    {
        config(['queue.default' => 'database']);

        new ExampleAccountSeeder()->run();

        $this->assertGreaterThanOrEqual(15, Contact::query()->count());
        $this->assertDatabaseCount('jobs', 0);
    }

    #[Test]
    public function it_can_be_run_again_without_building_a_second_account(): void
    {
        new ExampleAccountSeeder()->run();

        $contacts = Contact::query()->count();

        new ExampleAccountSeeder()->run();

        $this->assertCount(1, User::all());
        $this->assertEquals($contacts, Contact::query()->count());
    }
}
