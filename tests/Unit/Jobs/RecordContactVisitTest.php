<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\RecordContactVisit;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecordContactVisitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_the_moment_the_page_was_opened_rather_than_the_moment_the_queue_got_to_it(): void
    {
        $contact = Contact::factory()->create(['first_name' => 'Kevin', 'last_name' => 'Malone', 'last_consulted_at' => null]);
        $openedAt = now()->subMinutes(5)->toImmutable();

        new RecordContactVisit(contact: $contact, visitedAt: $openedAt)->handle();

        $contact->refresh();

        $this->assertEqualsWithDelta($openedAt->timestamp, $contact->last_consulted_at->timestamp, 2);
    }

    #[Test]
    public function it_leaves_the_moment_the_contact_was_edited_alone(): void
    {
        $contact = Contact::factory()->create(['first_name' => 'Kevin', 'last_name' => 'Malone']);
        $editedAt = now()->subWeek();

        Contact::withoutTimestamps(fn () => $contact->forceFill(['updated_at' => $editedAt])->saveQuietly());

        new RecordContactVisit(contact: $contact)->handle();

        $contact->refresh();

        $this->assertNotNull($contact->last_consulted_at);
        $this->assertEqualsWithDelta($editedAt->timestamp, $contact->updated_at->timestamp, 2);
    }
}
