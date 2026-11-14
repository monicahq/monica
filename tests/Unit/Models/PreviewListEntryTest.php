<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\PreviewListEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PreviewListEntryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_finds_the_entry_an_admission_token_belongs_to(): void
    {
        $entry = PreviewListEntry::factory()->admitted('dwight-schrute-token')->create();

        $this->assertTrue($entry->is(PreviewListEntry::findByAdmissionToken('dwight-schrute-token')));
    }

    #[Test]
    public function it_finds_nothing_for_an_unknown_or_empty_token(): void
    {
        PreviewListEntry::factory()->admitted('dwight-schrute-token')->create();

        $this->assertNull(PreviewListEntry::findByAdmissionToken('jim-halpert-token'));
        $this->assertNull(PreviewListEntry::findByAdmissionToken(''));
    }

    #[Test]
    public function it_finds_nothing_once_the_link_has_expired(): void
    {
        PreviewListEntry::factory()->admitted('dwight-schrute-token')->create([
            'token_expires_at' => now()->subMinute(),
        ]);

        $this->assertNull(PreviewListEntry::findByAdmissionToken('dwight-schrute-token'));
    }

    #[Test]
    public function it_finds_nothing_once_the_entry_is_registered(): void
    {
        PreviewListEntry::factory()->admitted('dwight-schrute-token')->create([
            'registered_at' => now(),
        ]);

        $this->assertNull(PreviewListEntry::findByAdmissionToken('dwight-schrute-token'));
    }

    #[Test]
    public function it_finds_nothing_for_a_token_another_admission_replaced(): void
    {
        $entry = PreviewListEntry::factory()->admitted('dwight-schrute-token')->create();

        $entry->update(['token' => hash('sha256', 'a-newer-token')]);

        $this->assertNull(PreviewListEntry::findByAdmissionToken('dwight-schrute-token'));
    }

    #[Test]
    public function it_writes_to_the_entry_in_the_language_it_joined_in(): void
    {
        $entry = PreviewListEntry::factory()->make(['locale' => 'fr_FR']);

        $this->assertEquals('fr_FR', $entry->preferredLocale());
    }
}
