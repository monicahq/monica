<?php

declare(strict_types=1);

namespace Tests\Unit\Seeders;

use App\Enums\AddressLabelEnum;
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
    public function it_puts_monica_in_as_the_only_user_and_the_me_contact_of_the_vault(): void
    {
        new ExampleAccountSeeder()->run();

        $this->assertCount(1, User::all());
        $this->assertDatabaseHas('users', [
            'first_name' => 'Monica',
            'last_name' => 'Geller',
            'email' => ExampleAccountSeeder::EMAIL,
        ]);
        $this->assertNotNull(User::query()->firstOrFail()->email_verified_at);

        $vault = Vault::query()->firstOrFail();
        $me = Contact::query()->where('vault_id', $vault->id)->where('is_me', true)->firstOrFail();

        $this->assertEquals('Monica', $me->first_name);
        $this->assertEquals('Geller', $me->last_name);
        $this->assertCount(1, Contact::query()->where('is_me', true)->get());
    }

    #[Test]
    public function it_fills_the_vault_with_the_rest_of_the_cast_as_the_show_gives_them(): void
    {
        new ExampleAccountSeeder()->run();

        $this->assertGreaterThanOrEqual(15, Contact::query()->count());

        foreach (['Rachel', 'Ross', 'Chandler', 'Joey', 'Phoebe'] as $firstName) {
            $this->assertDatabaseHas('contacts', ['first_name' => $firstName]);
        }

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

        $gunther = Contact::query()->where('first_name', 'Gunther')->firstOrFail();
        $home = FieldValue::query()->where('valuable_id', $joey->id)->where('field_key', DefaultFieldEnum::Address->value)->sole();
        $work = FieldValue::query()->where('valuable_id', $gunther->id)->where('field_key', DefaultFieldEnum::Address->value)->sole();

        $this->assertEquals([
            'po_box' => '',
            'extended' => '',
            'street' => '90 Bedford Street, apartment 19',
            'city' => 'New York',
            'region' => '',
            'postal_code' => 'NY 10014',
            'country' => 'United States',
        ], $home->value_json);
        $this->assertSame(AddressLabelEnum::Home->value, $home->valueListItem->key);
        $this->assertSame(AddressLabelEnum::Work->value, $work->valueListItem->key);
        $ross = Contact::query()->where('first_name', 'Ross')->firstOrFail();
        $category = FieldValue::query()
            ->where('valuable_id', $ross->id)
            ->where('field_key', DefaultFieldEnum::Category->value)
            ->firstOrFail();
        $family = ValueListItem::query()->where('key', ContactCategoryEnum::Family->value)->firstOrFail();

        $this->assertEquals([$family->id], $category->value_list_item_ids);

        $this->assertDatabaseMissing('field_values', [
            'valuable_id' => $gunther->id,
            'field_key' => DefaultFieldEnum::Birthday->value,
        ]);
        $this->assertFalse(Reminder::query()->where('remindable_id', $gunther->id)->exists());

        $favourites = Contact::query()->where('is_favourite', true)->pluck('first_name');

        $this->assertCount(3, $favourites);
        $this->assertContains('Rachel', $favourites);
        $this->assertContains('Ross', $favourites);
        $this->assertContains('Chandler', $favourites);

        $this->assertTrue(Contact::query()->where('last_name', 'Heckles')->firstOrFail()->is_archived);
    }

    #[Test]
    public function it_sets_a_different_reminder_on_every_birthday_but_monicas_own(): void
    {
        new ExampleAccountSeeder()->run();

        $birthdays = FieldValue::query()->where('field_key', DefaultFieldEnum::Birthday->value)->count();
        $monica = Contact::query()->where('is_me', true)->firstOrFail();
        $reminders = Reminder::query()->get();

        $this->assertEquals($birthdays - 1, $reminders->count());
        $this->assertGreaterThanOrEqual(5, $reminders->count());
        $this->assertEquals($reminders->count(), $reminders->where('is_active', true)->count());
        $this->assertFalse(Reminder::query()->where('remindable_id', $monica->id)->exists());
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
