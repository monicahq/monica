<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Models\PreviewListEntry;
use App\Notifications\PreviewListEntryAdmitted;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PreviewListEntryAdmittedTest extends TestCase
{
    #[Test]
    public function it_carries_the_link_that_opens_sign_up(): void
    {
        $entry = PreviewListEntry::factory()->make();

        $message = new PreviewListEntryAdmitted(token: 'toby-flenderson-token')->toMail($entry);

        $this->assertEquals('You can now create your account on '.config('app.name'), $message->subject);
        $this->assertEquals(route('auth.previewAdmission.show', ['token' => 'toby-flenderson-token']), $message->actionUrl);
        $this->assertStringContainsString('only for the next 14 days', (string) $message->render());
    }

    #[Test]
    public function it_goes_out_on_the_high_queue(): void
    {
        $this->assertEquals('high', new PreviewListEntryAdmitted(token: 'toby-flenderson-token')->queue);
    }
}
