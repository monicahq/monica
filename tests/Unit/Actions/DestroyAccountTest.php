<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyAccount;
use App\Exceptions\ProviderUnreachable;
use App\Models\Account;
use App\Models\AccountExport;
use App\Models\AccountImport;
use App\Models\EmailSent;
use App\Models\Log;
use App\Models\MagicLink;
use App\Models\Payment;
use App\Models\SupportAttachment;
use App\Models\SupportMessage;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Features\ExampleCapability;
use Tests\TestCase;

class DestroyAccountTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_destroys_the_account(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        new DestroyAccount(
            user: $user,
            account: $account,
        )->execute();

        $this->assertModelMissing($account);
    }

    #[Test]
    public function it_takes_everything_under_the_account_with_it(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);
        $log = Log::factory()->create(['account_id' => $account->id, 'user_id' => $user->id]);
        $emailSent = EmailSent::factory()->create(['account_id' => $account->id, 'user_id' => $user->id]);
        $magicLink = MagicLink::factory()->create(['user_id' => $user->id]);
        $apiKey = $user->createToken('Dundie awards bot')->accessToken;

        new DestroyAccount(
            user: $user,
            account: $account,
        )->execute();

        $this->assertModelMissing($account);
        $this->assertModelMissing($user);
        $this->assertModelMissing($log);
        $this->assertModelMissing($emailSent);
        $this->assertModelMissing($magicLink);
        $this->assertModelMissing($apiKey);
    }

    #[Test]
    public function it_forgets_the_flags_switched_for_the_account(): void
    {
        Feature::discover('Tests\Fixtures\Features', base_path('tests/Fixtures/Features'));

        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);
        $neighbour = Account::factory()->create();
        $account->features()->activate(ExampleCapability::class);
        $neighbour->features()->activate(ExampleCapability::class);

        new DestroyAccount(
            user: $user,
            account: $account,
        )->execute();

        $this->assertDatabaseMissing('features', ['scope' => Feature::serializeScope($account)]);
        $this->assertDatabaseHas('features', ['scope' => Feature::serializeScope($neighbour)]);
    }

    #[Test]
    public function it_deletes_the_photo_of_the_user_from_the_disk(): void
    {
        Storage::fake();

        $account = Account::factory()->create();
        $user = User::factory()->create([
            'account_id' => $account->id,
            'photo_path' => 'photos/1/dwight',
        ]);

        foreach (User::photoPixelSizes() as $pixels) {
            Storage::put($user->photoVariantPath($pixels), 'a beet farm');
        }

        new DestroyAccount(
            user: $user,
            account: $account,
        )->execute();

        foreach (User::photoPixelSizes() as $pixels) {
            Storage::assertMissing($user->photoVariantPath($pixels));
        }
    }

    #[Test]
    public function it_deletes_the_export_and_import_files_of_the_account_from_every_disk(): void
    {
        Storage::fake('local');
        Storage::fake('s3');

        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);
        $export = AccountExport::factory()->create(['user_id' => $user->id, 'disk' => 's3']);
        $import = AccountImport::factory()->create(['user_id' => $user->id, 'disk' => 'local']);
        $stamford = AccountExport::factory()->create(['disk' => 's3']);
        Storage::disk('s3')->put($export->path, '{}');
        Storage::disk('local')->put($import->path, '{}');
        Storage::disk('s3')->put($stamford->path, '{}');

        new DestroyAccount(
            user: $user,
            account: $account,
        )->execute();

        Storage::disk('s3')->assertMissing($export->path);
        Storage::disk('local')->assertMissing($import->path);
        Storage::disk('s3')->assertExists($stamford->path);
    }

    #[Test]
    public function it_deletes_the_files_sent_to_the_support_team_from_the_disk(): void
    {
        Storage::fake('local');

        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);
        $message = SupportMessage::factory()->create([
            'support_request_id' => SupportRequest::factory()->create(['account_id' => $account->id])->id,
        ]);
        $screenshot = SupportAttachment::factory()->create(['support_message_id' => $message->id, 'disk' => 'local']);
        $stamford = SupportAttachment::factory()->create(['disk' => 'local']);
        Storage::disk('local')->put($screenshot->path, 'png');
        Storage::disk('local')->put($stamford->path, 'png');

        new DestroyAccount(
            user: $user,
            account: $account,
        )->execute();

        Storage::disk('local')->assertMissing($screenshot->path);
        Storage::disk('local')->assertExists($stamford->path);
        $this->assertModelMissing($screenshot);
    }

    #[Test]
    public function it_throws_when_the_user_belongs_to_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $account = Account::factory()->create();
        $stranger = User::factory()->create();

        new DestroyAccount(
            user: $stranger,
            account: $account,
        )->execute();
    }

    #[Test]
    public function it_stops_a_running_subscription_before_deleting_anything(): void
    {
        $this->fakeStripe();

        $michael = User::factory()->create();
        $subscription = $this->subscribeAccount($michael->account);

        new DestroyAccount(user: $michael, account: $michael->account)->execute();

        $this->assertModelMissing($michael->account);
        $this->assertModelMissing($subscription);
    }

    #[Test]
    public function it_deletes_the_payments_with_the_account(): void
    {
        $this->fakeStripe();

        $michael = User::factory()->create();
        $payment = Payment::factory()->create(['account_id' => $michael->account_id]);

        new DestroyAccount(user: $michael, account: $michael->account)->execute();

        $this->assertModelMissing($payment);
    }

    #[Test]
    public function it_deletes_nothing_when_stripe_will_not_stop_the_subscription(): void
    {
        $this->expectException(ProviderUnreachable::class);

        $client = $this->fakeStripe();
        $client->failOn('subscriptions*');

        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account);

        try {
            new DestroyAccount(user: $michael, account: $michael->account)->execute();
        } finally {
            $this->assertModelExists($michael->account);
            $this->assertModelExists($michael);
        }
    }

    #[Test]
    public function it_never_asks_stripe_anything_about_an_account_that_never_paid(): void
    {
        $client = $this->fakeStripe();

        $michael = User::factory()->create();

        new DestroyAccount(user: $michael, account: $michael->account)->execute();

        $this->assertModelMissing($michael->account);
        $this->assertSame([], $client->requests());
    }
}
