<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Enums\EmailDeliveryStatusEnum;
use App\Enums\TimeFormatEnum;
use App\Models\Account;
use App\Models\Contact;
use App\Models\EmailSent;
use App\Models\MarketplaceListingVersion;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Admin\OverviewViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OverviewViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_counts_what_the_instance_holds(): void
    {
        $michael = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $michael->account_id]);
        $template = Template::factory()->create(['account_id' => $michael->account_id]);
        $version = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
        Contact::factory()->count(3)->create([
            'account_id' => $michael->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->id,
        ]);
        Account::factory()->create();

        $viewModel = new OverviewViewModel(user: $this->viewer());

        $this->assertEquals(2, $viewModel->accountCount());
        $this->assertEquals(1, $viewModel->userCount());
        $this->assertEquals(1, $viewModel->vaultCount());
        $this->assertEquals(3, $viewModel->contactCount());
    }

    #[Test]
    public function it_adds_up_the_storage_every_account_holds(): void
    {
        Account::factory()->create(['storage_used_in_bytes' => 3 * 1024 * 1024]);
        Account::factory()->create(['storage_used_in_bytes' => 2 * 1024 * 1024]);

        $viewModel = new OverviewViewModel(user: $this->viewer());

        $this->assertEquals(5 * 1024 * 1024, $viewModel->storageUsedInBytes());
        $this->assertEquals('5 MB', $viewModel->storageUsed());
    }

    #[Test]
    public function it_counts_who_signed_in_over_the_last_week(): void
    {
        User::factory()->create(['last_login_at' => now()->subDay()]);
        User::factory()->create(['last_login_at' => now()->subDays(6)]);
        User::factory()->create(['last_login_at' => now()->subDays(30)]);
        User::factory()->create(['last_login_at' => null]);

        $this->assertEquals(2, new OverviewViewModel(user: $this->viewer())->signedInRecently());
    }

    #[Test]
    public function it_counts_the_accounts_opened_over_the_last_month(): void
    {
        Account::factory()->create(['created_at' => now()->subDays(2)]);
        Account::factory()->create(['created_at' => now()->subDays(29)]);
        Account::factory()->create(['created_at' => now()->subDays(60)]);

        $this->assertEquals(2, new OverviewViewModel(user: $this->viewer())->accountsCreatedRecently());
    }

    #[Test]
    public function it_counts_the_submissions_nobody_has_decided(): void
    {
        MarketplaceListingVersion::factory()->count(2)->create();
        MarketplaceListingVersion::factory()->approved()->create();
        MarketplaceListingVersion::factory()->withdrawn()->create();

        $this->assertEquals(2, new OverviewViewModel(user: $this->viewer())->awaitingModeration());
    }

    #[Test]
    public function it_reads_as_an_empty_instance_rather_than_as_an_error(): void
    {
        $viewModel = new OverviewViewModel(user: $this->viewer());

        $this->assertTrue($viewModel->isEmpty());
        $this->assertEquals(0, $viewModel->accountCount());
        $this->assertEquals(0, $viewModel->userCount());
        $this->assertEquals(0, $viewModel->vaultCount());
        $this->assertEquals(0, $viewModel->contactCount());
        $this->assertEquals(0, $viewModel->signedInRecently());
        $this->assertEquals(0, $viewModel->accountsCreatedRecently());
        $this->assertEquals(0, $viewModel->awaitingModeration());
        $this->assertEquals('0 B', $viewModel->storageUsed());
    }

    #[Test]
    public function it_counts_the_emails_that_left_and_where_each_got_to(): void
    {
        EmailSent::factory()->count(2)->delivered()->create();
        EmailSent::factory()->bounced()->create();
        EmailSent::factory()->complained()->create();
        EmailSent::factory()->count(3)->create();

        $viewModel = new OverviewViewModel(user: $this->viewer());

        $this->assertSame([
            EmailDeliveryStatusEnum::Delivered->value => 2,
            EmailDeliveryStatusEnum::Bounced->value => 1,
            EmailDeliveryStatusEnum::Complained->value => 1,
            EmailDeliveryStatusEnum::Sent->value => 3,
        ], $viewModel->emailsSentByStatus());

        $this->assertSame(7, $viewModel->emailsSentCount());
    }

    #[Test]
    public function it_agrees_with_what_each_row_says_about_itself(): void
    {
        EmailSent::factory()->delivered()->create();
        EmailSent::factory()->delivered()->bounced()->create();
        EmailSent::factory()->bounced()->complained()->create();
        EmailSent::factory()->delivered()->bounced()->complained()->create();
        EmailSent::factory()->create();

        $fromTheRows = EmailSent::query()->get()
            ->countBy(fn (EmailSent $emailSent): string => $emailSent->delivery_status->value)
            ->all();

        $counted = array_filter(new OverviewViewModel(user: $this->viewer())->emailsSentByStatus());

        ksort($fromTheRows);
        ksort($counted);

        $this->assertSame($fromTheRows, $counted);
    }

    #[Test]
    public function it_counts_no_email_on_an_instance_that_has_sent_none(): void
    {
        $viewModel = new OverviewViewModel(user: $this->viewer());

        $this->assertSame(0, $viewModel->emailsSentCount());
        $this->assertSame([0, 0, 0, 0], array_values($viewModel->emailsSentByStatus()));
    }

    #[Test]
    public function it_says_when_it_counted_in_the_clock_of_whoever_is_looking(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 9)->setTime(19, 32, 0));
        $michael = new User()->forceFill(['timezone' => 'America/New_York', 'time_format' => TimeFormatEnum::TwelveHour]);

        $this->assertSame('2:32 PM', new OverviewViewModel(user: $michael)->countedAt());
    }

    #[Test]
    public function it_says_the_queue_is_empty_rather_than_counting_nothing(): void
    {
        $this->assertSame('Nothing in the moderation queue right now.', new OverviewViewModel(user: $this->viewer())->moderationSentence());
    }

    #[Test]
    public function it_says_how_many_submissions_are_waiting(): void
    {
        MarketplaceListingVersion::factory()->count(2)->create();

        $this->assertSame('2 marketplace submissions are waiting for a decision.', new OverviewViewModel(user: $this->viewer())->moderationSentence());
    }

    #[Test]
    public function it_names_the_period_each_recent_figure_covers(): void
    {
        $viewModel = new OverviewViewModel(user: $this->viewer());

        $this->assertSame('signed in, last 7 days', $viewModel->signedInLabel());
        $this->assertSame('signed up, last 30 days', $viewModel->newAccountsLabel());
    }

    #[Test]
    public function it_shares_the_emails_out_by_where_they_got_to(): void
    {
        EmailSent::factory()->count(6)->delivered()->create();
        EmailSent::factory()->count(2)->bounced()->create();
        EmailSent::factory()->complained()->create();
        EmailSent::factory()->create();

        $viewModel = new OverviewViewModel(user: $this->viewer());

        $this->assertSame([
            EmailDeliveryStatusEnum::Delivered->value => 60.0,
            EmailDeliveryStatusEnum::Bounced->value => 20.0,
            EmailDeliveryStatusEnum::Complained->value => 10.0,
            EmailDeliveryStatusEnum::Sent->value => 10.0,
        ], $viewModel->emailShares());
        $this->assertSame('10 emails sent: 6 delivered, 2 bounced, 1 marked as spam, 1 on its way.', $viewModel->emailSummary());
    }

    #[Test]
    public function it_shares_nothing_out_on_an_instance_that_has_sent_no_email(): void
    {
        $viewModel = new OverviewViewModel(user: $this->viewer());

        $this->assertSame([0.0, 0.0, 0.0, 0.0], array_values($viewModel->emailShares()));
        $this->assertSame('No email sent yet.', $viewModel->emailSummary());
    }

    private function viewer(): User
    {
        return new User()->forceFill(['timezone' => 'UTC', 'time_format' => TimeFormatEnum::TwentyFourHour]);
    }
}
