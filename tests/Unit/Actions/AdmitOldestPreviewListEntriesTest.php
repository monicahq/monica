<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\AdmitOldestPreviewListEntries;
use App\Models\PreviewListEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdmitOldestPreviewListEntriesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_admits_the_oldest_waiting_entries_first(): void
    {
        Queue::fake();
        Notification::fake();

        $newest = PreviewListEntry::factory()->create(['created_at' => now()->subDay()]);
        $oldest = PreviewListEntry::factory()->create(['created_at' => now()->subDays(3)]);
        $middle = PreviewListEntry::factory()->create(['created_at' => now()->subDays(2)]);
        $alreadyAdmitted = PreviewListEntry::factory()->admitted('a-token')->create(['created_at' => now()->subDays(9)]);

        $admitted = new AdmitOldestPreviewListEntries(administrator: User::factory()->create(), count: 2)->execute();

        $this->assertEquals(2, $admitted);
        $this->assertNotNull($oldest->refresh()->admitted_at);
        $this->assertNotNull($middle->refresh()->admitted_at);
        $this->assertNull($newest->refresh()->admitted_at);
        $this->assertTrue($alreadyAdmitted->is(PreviewListEntry::findByAdmissionToken('a-token')));
    }

    #[Test]
    public function it_admits_everybody_waiting_when_fewer_wait_than_asked(): void
    {
        Queue::fake();
        Notification::fake();

        PreviewListEntry::factory()->count(2)->create();

        $admitted = new AdmitOldestPreviewListEntries(administrator: User::factory()->create(), count: 10)->execute();

        $this->assertEquals(2, $admitted);
    }

    #[Test]
    public function it_skips_an_address_that_has_come_to_hold_an_account(): void
    {
        Queue::fake();
        Notification::fake();

        $taken = PreviewListEntry::factory()->create(['email' => 'jan.levinson@dundermifflin.com', 'created_at' => now()->subDays(2)]);
        $free = PreviewListEntry::factory()->create(['created_at' => now()->subDay()]);
        User::factory()->create(['email' => 'jan.levinson@dundermifflin.com']);

        $admitted = new AdmitOldestPreviewListEntries(administrator: User::factory()->create(), count: 2)->execute();

        $this->assertEquals(1, $admitted);
        $this->assertNull($taken->refresh()->admitted_at);
        $this->assertNotNull($free->refresh()->admitted_at);
    }
}
