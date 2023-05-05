<?php

namespace Tests\Unit\Domains\Vault\ManageCalendar\Web\ViewHelpers;

use App\Domains\Vault\ManageCalendar\Web\ViewHelpers\VaultCalendarIndexViewHelper;
use App\Helpers\ContactCardHelper;
use App\Models\Contact;
use App\Models\ContactImportantDate;
use App\Models\ContactImportantDateType;
use App\Models\Journal;
use App\Models\MoodTrackingEvent;
use App\Models\MoodTrackingParameter;
use App\Models\Post;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VaultCalendarIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);

        $array = VaultCalendarIndexViewHelper::data(
            vault: $vault,
            user: $user,
            year: 2023,
            month: 4
        );

        $this->assertCount(
            5,
            $array
        );
        $this->assertEquals(
            'April 2023',
            $array['current_month']
        );
        $this->assertEquals(
            'May 2023',
            $array['next_month']
        );
        $this->assertEquals(
            'March 2023',
            $array['previous_month']
        );
        $this->assertEquals(
            [
                'previous' => env('APP_URL').'/vaults/'.$vault->id.'/calendar/years/2023/months/3',
                'next' => env('APP_URL').'/vaults/'.$vault->id.'/calendar/years/2023/months/5',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_builds_the_monthly_calendar(): void
    {
        Carbon::setTestNow(Carbon::create(2023, 4, 2));
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $collection = VaultCalendarIndexViewHelper::buildMonth(
            vault: $vault,
            user: $user,
            year: 2023,
            month: 4
        );

        $this->assertEquals(5, $collection->count());
    }

    /** @test */
    public function it_gets_the_important_dates(): void
    {
        $contact = Contact::factory()->create();
        $type = ContactImportantDateType::factory()->create([
            'label' => 'type',
        ]);
        $contactImportantDate = ContactImportantDate::factory()->create([
            'contact_important_date_type_id' => $type->id,
            'contact_id' => $contact->id,
            'day' => '03',
            'month' => '03',
            'label' => 'date',
        ]);

        $contactIds = Contact::all()->pluck('id');

        $collection = VaultCalendarIndexViewHelper::getImportantDates(3, 3, $contactIds);

        $this->assertEquals(1, $collection->count());

        $this->assertEquals(
            [
                0 => [
                    'id' => $contactImportantDate->id,
                    'label' => 'date',
                    'type' => [
                        'id' => $type->id,
                        'label' => 'type',
                    ],
                    'contact' => ContactCardHelper::data($contactImportantDate->contact),
                ],
            ],
            $collection->toArray()
        );
    }

    /** @test */
    public function it_gets_mood(): void
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = $user->getContactInVault($vault);
        $moodTrackingParameter = MoodTrackingParameter::factory()->create([
            'label' => 'type',
            'hex_color' => '#000000',
        ]);
        $mood = MoodTrackingEvent::factory()->create([
            'mood_tracking_parameter_id' => $moodTrackingParameter->id,
            'contact_id' => $contact->id,
            'rated_at' => '2023-03-03',
            'note' => 'this is a note',
            'number_of_hours_slept' => 8,
        ]);
        $date = CarbonImmutable::create(2023, 3, 3);

        $collection = VaultCalendarIndexViewHelper::getMood($vault, $user, $date);

        $this->assertEquals(1, $collection->count());

        $this->assertEquals(
            [
                0 => [
                    'id' => $mood->id,
                    'note' => 'this is a note',
                    'number_of_hours_slept' => 8,
                    'mood_tracking_parameter' => [
                        'id' => $moodTrackingParameter->id,
                        'label' => 'type',
                        'hex_color' => '#000000',
                    ],
                ],
            ],
            $collection->toArray()
        );
    }

    /** @test */
    public function it_gets_journal_entries(): void
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $journal = Journal::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $post = Post::factory()->create([
            'journal_id' => $journal->id,
            'written_at' => '2023-03-03',
            'title' => 'this is a title',
        ]);
        $date = CarbonImmutable::create(2023, 3, 3);

        $collection = VaultCalendarIndexViewHelper::getJournalEntries($vault, $date);

        $this->assertEquals(1, $collection->count());

        $this->assertEquals(
            [
                0 => [
                    'id' => $post->id,
                    'title' => 'this is a title',
                    'url' => [
                        'show' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/posts/'.$post->id,
                    ],
                ],
            ],
            $collection->toArray()
        );
    }
}
