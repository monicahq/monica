<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\ChangelogEntryTypeEnum;
use App\Helpers\ChangelogEntries;
use App\Helpers\ChangelogEntry;
use App\Models\ChangelogSeenEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChangelogEntriesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_every_field_of_an_entry(): void
    {
        $entry = $this->valid()->find('01M7NBFWK5XNH908RFDGEAJXKN');

        $this->assertInstanceOf(ChangelogEntry::class, $entry);
        $this->assertSame('Book the conference room from a contact', $entry->title);
        $this->assertSame(ChangelogEntryTypeEnum::Feature, $entry->type);
        $this->assertSame('Contacts', $entry->area);
        $this->assertSame('/vaults', $entry->link);
        $this->assertSame('2026-11-16T09:00:00+00:00', $entry->publishedAt->toIso8601String());
        $this->assertSame('Michael can now book the conference room **straight** from a contact page.', $entry->body);
        $this->assertSame('conference-room-booking.md', $entry->file);
    }

    #[Test]
    public function it_removes_the_quotes_around_a_value(): void
    {
        $this->assertSame('Vault', $this->valid()->find('01M7NBFWKB8S8MJ0MBVV165X36')?->area);
    }

    #[Test]
    public function it_lists_entries_newest_first(): void
    {
        $ids = array_map(fn (ChangelogEntry $entry): string => $entry->id, $this->valid()->visible());

        $this->assertSame([
            '01M7NBFWKB8S8MJ0MBVV165X36',
            '01M7NBFWK9KQA94HRSS9BGJJ8K',
            '01M7NBFWK5XNH908RFDGEAJXKN',
            '01M7NBFWK7BEESY8WABBMXF3E6',
            '01M7NBFWKD8ZJ060BP6ZNF2NFG',
        ], $ids);
    }

    #[Test]
    public function it_keeps_an_entry_dated_in_the_future_out_of_sight(): void
    {
        $entries = $this->valid();

        $this->assertCount(6, $entries->all());
        $this->assertNull($entries->find('01M7NBFWKFAM1JXYF0A7RKJ865'));
    }

    #[Test]
    public function it_reads_a_file_again_once_it_changes(): void
    {
        $directory = sys_get_temp_dir().'/changelog-'.uniqid();
        File::ensureDirectoryExists($directory);
        File::put("{$directory}/entry.md", "---\nid: 01M7NBFWK5XNH908RFDGEAJXKN\ntitle: Before\ntype: fix\npublished_at: 2026-11-16T09:00:00Z\n---\nBody.\n");
        $entries = new ChangelogEntries(directory: $directory);

        $this->assertSame('Before', $entries->all()[0]->title);

        File::put("{$directory}/entry.md", "---\nid: 01M7NBFWK5XNH908RFDGEAJXKN\ntitle: After\ntype: fix\npublished_at: 2026-11-16T09:00:00Z\n---\nBody.\n");
        touch("{$directory}/entry.md", time() + 10);
        clearstatcache();

        $this->assertSame('After', $entries->all()[0]->title);

        File::deleteDirectory($directory);
    }

    #[Test]
    public function it_reads_the_entries_back_from_a_cache_that_refuses_to_unserialize_objects(): void
    {
        config(['cache.stores.array.serialize' => true, 'cache.serializable_classes' => false]);
        Cache::forgetDriver('array');
        $entries = $this->valid();

        $entries->all();
        $entry = $entries->find('01M7NBFWK5XNH908RFDGEAJXKN');

        $this->assertInstanceOf(ChangelogEntry::class, $entry);
        $this->assertSame(ChangelogEntryTypeEnum::Feature, $entry->type);
        $this->assertSame('2026-11-16T09:00:00+00:00', $entry->publishedAt->toIso8601String());
    }

    #[Test]
    public function it_orders_a_period_by_day_with_the_fixes_last(): void
    {
        $ids = array_map(fn (ChangelogEntry $entry): string => $entry->id, $this->valid()->publishedWithin(30));

        $this->assertSame([
            '01M7NBFWKB8S8MJ0MBVV165X36',
            '01M7NBFWK5XNH908RFDGEAJXKN',
            '01M7NBFWK9KQA94HRSS9BGJJ8K',
            '01M7NBFWK7BEESY8WABBMXF3E6',
        ], $ids);
        $this->assertCount(5, $this->valid()->publishedWithin(90));
    }

    #[Test]
    public function it_reads_the_period_asked_for_and_falls_back_to_thirty_days(): void
    {
        $this->assertSame(90, ChangelogEntries::period('90'));
        $this->assertSame(30, ChangelogEntries::period('365'));
        $this->assertSame(30, ChangelogEntries::period(null));
    }

    #[Test]
    public function it_counts_as_unseen_what_was_published_since_the_person_joined_and_not_seen(): void
    {
        $entries = $this->valid();
        $user = User::factory()->create(['created_at' => '2026-11-01 00:00:00']);
        ChangelogSeenEntry::factory()->create(['user_id' => $user->id, 'entry_id' => '01M7NBFWK5XNH908RFDGEAJXKN']);

        $ids = array_map(fn (ChangelogEntry $entry): string => $entry->id, $entries->unseenBy($user));

        $this->assertEqualsCanonicalizing(['01M7NBFWKB8S8MJ0MBVV165X36', '01M7NBFWK9KQA94HRSS9BGJJ8K', '01M7NBFWK7BEESY8WABBMXF3E6'], $ids);
    }

    #[Test]
    public function it_finds_no_problem_in_valid_files(): void
    {
        $this->assertSame([], $this->valid()->problems());
    }

    #[Test]
    public function it_names_each_defect_and_the_file_holding_it(): void
    {
        $entries = new ChangelogEntries(directory: base_path('tests/Fixtures/Changelog/Invalid'));
        $problems = implode("\n", $entries->problems());

        $this->assertCount(9, $entries->problems());
        $this->assertStringContainsString('missing-id.md: id must be a ULID', $problems);
        $this->assertStringContainsString('bad-ulid.md: id must be a ULID', $problems);
        $this->assertStringContainsString('duplicate-second.md: the id 01M7NBFWKH63PHSPN4KJQXXX7A is already used by duplicate-first.md', $problems);
        $this->assertStringContainsString('bad-type.md: type must be feature, improvement, fix', $problems);
        $this->assertStringContainsString('bad-date.md: published_at must be', $problems);
        $this->assertStringContainsString('link-scheme.md: link must be a path', $problems);
        $this->assertStringContainsString('link-protocol-relative.md: link must be a path', $problems);
        $this->assertStringContainsString('link-backslash.md: link must be a path', $problems);
        $this->assertStringContainsString('empty-body.md: the description under the front matter is empty', $problems);
    }

    #[Test]
    public function it_shows_an_entry_whose_link_is_refused_without_the_link(): void
    {
        $entries = new ChangelogEntries(directory: base_path('tests/Fixtures/Changelog/Invalid'));
        $ids = array_map(fn (ChangelogEntry $entry): ?string => $entry->link, $entries->all());

        $this->assertNotNull($entries->find('01M7NBFWKRMG59R03MGF595BDP'));
        $this->assertSame([null, null, null, null], $ids);
    }

    #[Test]
    public function it_finds_no_problem_in_the_shipped_entries(): void
    {
        $this->assertSame([], new ChangelogEntries()->problems());
    }

    private function valid(): ChangelogEntries
    {
        CarbonImmutable::setTestNow('2026-11-17 12:00:00');

        return new ChangelogEntries(directory: base_path('tests/Fixtures/Changelog/Valid'));
    }
}
