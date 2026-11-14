<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\JoinPreviewList;
use App\Models\PreviewListEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class JoinPreviewListTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_a_new_address_on_the_list_as_waiting(): void
    {
        Notification::fake();

        new JoinPreviewList(
            email: ' Pam.Beesly@DunderMifflin.com ',
            locale: 'fr_FR',
        )->execute();

        $this->assertDatabaseHas('preview_list_entries', [
            'email' => 'pam.beesly@dundermifflin.com',
            'locale' => 'fr_FR',
            'admitted_at' => null,
            'registered_at' => null,
        ]);

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_adds_nothing_for_an_address_already_on_the_list(): void
    {
        $entry = PreviewListEntry::factory()->create(['email' => 'pam.beesly@dundermifflin.com', 'locale' => 'en']);

        new JoinPreviewList(
            email: 'PAM.BEESLY@dundermifflin.com',
            locale: 'fr_FR',
        )->execute();

        $this->assertDatabaseCount('preview_list_entries', 1);
        $this->assertEquals('en', $entry->refresh()->locale);
    }

    #[Test]
    public function it_adds_nothing_for_an_address_that_already_holds_an_account(): void
    {
        User::factory()->create(['email' => 'pam.beesly@dundermifflin.com']);

        new JoinPreviewList(
            email: 'pam.beesly@dundermifflin.com',
            locale: 'en',
        )->execute();

        $this->assertDatabaseCount('preview_list_entries', 0);
    }
}
