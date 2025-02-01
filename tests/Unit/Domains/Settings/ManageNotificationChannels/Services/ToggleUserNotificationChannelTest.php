<?php

namespace Tests\Unit\Domains\Settings\ManageNotificationChannels\Services;

use App\Domains\Settings\ManageNotificationChannels\Services\ToggleUserNotificationChannel;
use App\Models\Contact;
use App\Models\ContactReminder;
use App\Models\User;
use App\Models\UserNotificationChannel;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ToggleUserNotificationChannelTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_toggles_the_channel_if_the_channel_was_inactive(): void
    {
        $ross = $this->createUser();
        $channel = UserNotificationChannel::factory()->create([
            'user_id' => $ross->id,
            'active' => false,
        ]);
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $contactReminder = ContactReminder::factory()->create([
            'contact_id' => $contact->id,
            'type' => ContactReminder::TYPE_ONE_TIME,
            'day' => 2,
            'month' => 10,
            'year' => 2000,
        ]);
        $this->executeService($ross, $channel, $contactReminder);

        $this->assertDatabaseHas('user_notification_channels', [
            'id' => $channel->id,
            'user_id' => $ross->id,
            'active' => true,
        ]);

        $this->assertInstanceOf(
            UserNotificationChannel::class,
            $channel
        );

        $this->assertDatabaseHas('contact_reminder_scheduled', [
            'contact_reminder_id' => $contactReminder->id,
            'user_notification_channel_id' => $channel->id,
            'scheduled_at' => '2018-10-02 09:00:00',
        ]);
    }

    /** @test */
    public function it_toggles_the_channel_if_the_channel_was_active(): void
    {
        $ross = $this->createUser();
        $channel = UserNotificationChannel::factory()->create([
            'user_id' => $ross->id,
            'active' => true,
        ]);
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $contactReminder = ContactReminder::factory()->create([
            'contact_id' => $contact->id,
            'type' => ContactReminder::TYPE_ONE_TIME,
            'day' => 2,
            'month' => 10,
            'year' => 2000,
        ]);
        $this->executeService($ross, $channel, $contactReminder);

        $this->assertDatabaseHas('user_notification_channels', [
            'id' => $channel->id,
            'user_id' => $ross->id,
            'active' => false,
        ]);

        $this->assertInstanceOf(
            UserNotificationChannel::class,
            $channel
        );

        $this->assertDatabaseMissing('contact_reminder_scheduled', [
            'contact_reminder_id' => $contactReminder->id,
            'user_notification_channel_id' => $channel->id,
            'scheduled_at' => '2018-10-02 09:00:00',
        ]);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new ToggleUserNotificationChannel)->execute($request);
    }

    /** @test */
    public function it_fails_if_notification_channel_doesnt_belong_to_user(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $channel = UserNotificationChannel::factory()->create([
            'active' => false,
        ]);
        $this->executeService($ross, $channel);
    }

    private function executeService(User $author, UserNotificationChannel $channel, ?ContactReminder $contactReminder = null): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));

        $request = [
            'account_id' => $author->account_id,
            'author_id' => $author->id,
            'user_notification_channel_id' => $channel->id,
        ];

        $channel = (new ToggleUserNotificationChannel)->execute($request);
    }
}
