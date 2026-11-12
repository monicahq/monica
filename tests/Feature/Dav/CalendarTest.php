<?php

declare(strict_types=1);

namespace Tests\Feature\Dav;

use App\Enums\FieldTypeEnum;
use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\Fixtures\Dav\Structure;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $key;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->key = $this->user->createToken('Dwight’s phone')->plainTextToken;
    }

    #[Test]
    public function it_offers_two_calendars_per_vault(): void
    {
        $vault = $this->vault();

        $response = $this->dav('PROPFIND', 'dav/calendars/'.$this->user->email.'/', depth: '1');

        $response->assertStatus(207);
        $response->assertSee($vault->uuid.'-dates', escape: false);
        $response->assertSee($vault->uuid.'-reminders', escape: false);
    }

    #[Test]
    public function it_holds_one_yearly_entry_per_recurring_date(): void
    {
        $vault = $this->vault();
        $version = Structure::withFields($this->user, [
            ['type' => FieldTypeEnum::RecurringDate, 'key' => 'birthday', 'label' => 'Birthday'],
            ['type' => FieldTypeEnum::RecurringDate, 'key' => 'met-on', 'label' => 'The day we met'],
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'structure_version_id' => $version->id,
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
        ]);
        $known = $this->value($contact, $version, 0, ['value_string' => null, 'value_date' => '1984-01-20']);
        $unknown = $this->value($contact, $version, 1, [
            'value_string' => null,
            'value_date' => '1900-03-24',
            'year_is_unknown' => true,
        ]);

        $list = $this->dav('PROPFIND', $this->dates($vault), depth: '1');

        $list->assertStatus(207);
        $list->assertSee($contact->uuid.'-'.$known->id.'.ics', escape: false);
        $list->assertSee($contact->uuid.'-'.$unknown->id.'.ics', escape: false);

        $entry = $this->dav('GET', $this->dates($vault).$contact->uuid.'-'.$known->id.'.ics');

        $entry->assertOk();
        $body = $this->bodyOf($entry);
        $this->assertStringContainsString('BEGIN:VEVENT', $body);
        $this->assertStringContainsString('RRULE:FREQ=YEARLY', $body);
        $this->assertStringContainsString('DTSTART;VALUE=DATE:19840120', $body);
        $this->assertStringContainsString('Dwight Schrute', $body);
    }

    #[Test]
    public function it_dates_a_recurring_date_whose_year_is_unknown_in_this_year(): void
    {
        $vault = $this->vault();
        $version = Structure::withFields($this->user, [
            ['type' => FieldTypeEnum::RecurringDate, 'key' => 'met-on', 'label' => 'The day we met'],
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'structure_version_id' => $version->id,
        ]);
        $value = $this->value($contact, $version, 0, [
            'value_string' => null,
            'value_date' => '1900-03-24',
            'year_is_unknown' => true,
        ]);

        $entry = $this->dav('GET', $this->dates($vault).$contact->uuid.'-'.$value->id.'.ics');

        $entry->assertOk();
        $this->assertStringContainsString('DTSTART;VALUE=DATE:'.CarbonImmutable::now()->format('Y').'0324', $this->bodyOf($entry));
    }

    #[Test]
    public function it_holds_one_task_per_deadline_inside_the_horizon(): void
    {
        $vault = $this->vault();
        $ahead = $this->occurrence($vault, CarbonImmutable::now()->addDays(3), 'Wish Dwight a happy birthday');
        $beyond = $this->occurrence($vault, CarbonImmutable::now()->addDays(400), 'Far away');

        $list = $this->dav('PROPFIND', $this->reminders($vault), depth: '1');

        $list->assertStatus(207);
        $list->assertSee($ahead->uuid.'.ics', escape: false);
        $list->assertDontSee($beyond->uuid.'.ics', escape: false);

        $entry = $this->dav('GET', $this->reminders($vault).$ahead->uuid.'.ics');

        $entry->assertOk();
        $body = $this->bodyOf($entry);
        $this->assertStringContainsString('BEGIN:VTODO', $body);
        $this->assertStringContainsString('DUE:'.$ahead->scheduled_for->utc()->format('Ymd\THis\Z'), $body);
    }

    #[Test]
    public function it_leaves_out_a_deadline_that_was_answered_or_cancelled(): void
    {
        $vault = $this->vault();
        $open = $this->occurrence($vault, CarbonImmutable::now()->addDays(2), 'Still open');
        $answered = $this->occurrence($vault, CarbonImmutable::now()->subDay(), 'Answered', ['answer' => 'remembered', 'answered_at' => now()]);
        $cancelled = $this->occurrence($vault, CarbonImmutable::now()->addDays(2), 'Cancelled', ['cancelled_at' => now(), 'cancelled_reason' => 'record_archived']);

        $list = $this->dav('PROPFIND', $this->reminders($vault), depth: '1');

        $list->assertSee($open->uuid.'.ics', escape: false);
        $list->assertDontSee($answered->uuid.'.ics', escape: false);
        $list->assertDontSee($cancelled->uuid.'.ics', escape: false);
    }

    #[Test]
    public function it_tells_a_device_which_deadlines_were_answered_since_it_last_looked(): void
    {
        $vault = $this->vault();
        $stayed = $this->occurrence($vault, CarbonImmutable::now()->addDays(2), 'Order the paper for Scranton Business Park');

        $this->travel(2)->seconds();

        $answered = $this->occurrence($vault, CarbonImmutable::now()->subDay(), 'Call Jan about the Utica branch');
        $token = $this->reminderSyncToken($vault);

        $this->travel(2)->seconds();

        $answered->update(['answer' => 'remembered', 'answered_at' => now()]);
        $added = $this->occurrence($vault, CarbonImmutable::now()->addDays(5), 'Plan the Dundies');

        $response = $this->dav('REPORT', $this->reminders($vault), depth: '1', body: <<<XML
            <?xml version="1.0"?>
            <d:sync-collection xmlns:d="DAV:">
              <d:sync-token>$token</d:sync-token>
              <d:sync-level>1</d:sync-level>
              <d:prop><d:getetag/></d:prop>
            </d:sync-collection>
            XML);

        $response->assertStatus(207);
        $response->assertSee($added->uuid.'.ics', escape: false);
        $response->assertSee($answered->uuid.'.ics', escape: false);
        $response->assertDontSee($stayed->uuid.'.ics', escape: false);
    }

    #[Test]
    public function it_keeps_an_overdue_deadline_nobody_answered(): void
    {
        $vault = $this->vault();
        $overdue = $this->occurrence($vault, CarbonImmutable::now()->subDays(3), 'Send the photos from the wedding');

        $list = $this->dav('PROPFIND', $this->reminders($vault), depth: '1');

        $list->assertSee($overdue->uuid.'.ics', escape: false);
    }

    #[Test]
    public function it_refuses_every_write_a_device_tries_on_a_calendar(): void
    {
        $vault = $this->vault();
        $version = Structure::withFields($this->user, [
            ['type' => FieldTypeEnum::RecurringDate, 'key' => 'birthday', 'label' => 'Birthday'],
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'structure_version_id' => $version->id,
        ]);
        $value = $this->value($contact, $version, 0, ['value_string' => null, 'value_date' => '1984-01-20']);
        $entry = $this->dates($vault).$contact->uuid.'-'.$value->id.'.ics';

        $this->dav('PUT', $entry, contentType: 'text/calendar', body: "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nBEGIN:VEVENT\r\nUID:x\r\nDTSTART;VALUE=DATE:20260101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n")
            ->assertStatus(403);
        $this->dav('DELETE', $entry)->assertStatus(403);
        $this->dav('MKCALENDAR', 'dav/calendars/'.$this->user->email.'/invented/')->assertStatus(403);
        $this->dav('DELETE', $this->dates($vault))->assertStatus(403);

        $this->assertDatabaseHas('field_values', ['id' => $value->id]);
    }

    #[Test]
    public function it_hides_the_calendars_of_another_account(): void
    {
        $stranger = User::factory()->create();
        $theirs = Vault::factory()->create(['account_id' => $stranger->account_id]);

        $this->dav('PROPFIND', 'dav/calendars/'.$this->user->email.'/'.$theirs->uuid.'-dates/', depth: '1')
            ->assertStatus(404);
    }

    private function vault(string $name = 'Scranton'): Vault
    {
        return Vault::factory()->create(['account_id' => $this->user->account_id, 'name' => $name]);
    }

    private function dates(Vault $vault): string
    {
        return 'dav/calendars/'.$this->user->email.'/'.$vault->uuid.'-dates/';
    }

    private function reminders(Vault $vault): string
    {
        return 'dav/calendars/'.$this->user->email.'/'.$vault->uuid.'-reminders/';
    }

    private function reminderSyncToken(Vault $vault): string
    {
        $response = $this->dav('PROPFIND', $this->reminders($vault), body: <<<'XML'
            <?xml version="1.0"?>
            <d:propfind xmlns:d="DAV:"><d:prop><d:sync-token/></d:prop></d:propfind>
            XML);

        preg_match('#<d:sync-token>([^<]+)</d:sync-token>#', (string) $response->getContent(), $matches);

        return $matches[1] ?? '';
    }

    /** @param  array<string, mixed>  $attributes */
    private function occurrence(Vault $vault, CarbonImmutable $at, string $name, array $attributes = []): ReminderOccurrence
    {
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $reminder = Reminder::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'remindable_type' => $contact->getMorphClass(),
            'remindable_id' => $contact->id,
            'name' => $name,
        ]);

        return ReminderOccurrence::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'reminder_id' => $reminder->id,
            'scheduled_for' => $at,
            'local_date' => $at->toDateString(),
            ...$attributes,
        ]);
    }

    /** @param  array<string, mixed>  $attributes */
    private function value(Contact $contact, StructureVersion $version, int $index, array $attributes): FieldValue
    {
        $field = $version->sectionVersions()->with('fields')->get()
            ->flatMap(fn ($section) => $section->fields)
            ->values()
            ->all()[$index];

        return FieldValue::factory()->create([
            ...$attributes,
            'valuable_type' => $contact->getMorphClass(),
            'valuable_id' => $contact->id,
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'field_id' => $field->id,
            'field_key' => $field->key,
        ]);
    }

    private function bodyOf(TestResponse $response): string
    {
        return $response->baseResponse instanceof StreamedResponse
            ? $response->streamedContent()
            : (string) $response->getContent();
    }

    private function dav(string $method, string $uri, string $depth = '0', string $body = '', string $contentType = 'application/xml'): TestResponse
    {
        return $this->call($method, $uri, [], [], [], [
            'HTTP_AUTHORIZATION' => 'Basic '.base64_encode($this->user->email.':'.$this->key),
            'HTTP_DEPTH' => $depth,
            'CONTENT_TYPE' => $contentType,
        ], $body);
    }
}
