<?php

namespace Tests\Unit\Domains\Vault\ManageVault\Web\ViewHelpers;

use App\Models\Avatar;
use App\Models\Contact;
use App\Models\ContactReminder;
use App\Models\User;
use App\Models\UserNotificationChannel;
use App\Models\Vault;
use App\Vault\ManageVault\Web\ViewHelpers\VaultReminderIndexViewHelper;
use Carbon\Carbon;
use function env;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VaultReminderIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_upcoming_reminders_in_the_next_year(): void
    {
        Carbon::setTestNow(Carbon::create(2022, 1, 1));
        $user = User::factory()->create();
        $vault = Vault::factory()->create();
        $mitchell = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $avatar = Avatar::factory()->create([
            'contact_id' => $mitchell->id,
        ]);
        $mitchell->avatar_id = $avatar->id;
        $mitchell->save();
        $vault->users()->save($user, [
            'permission' => Vault::PERMISSION_MANAGE,
            'contact_id' => $mitchell->id,
        ]);

        $channel = UserNotificationChannel::factory()->create([
            'user_id' => $user->id,
            'active' => false,
        ]);
        $contactReminder = ContactReminder::factory()->create([
            'contact_id' => $mitchell->id,
            'type' => ContactReminder::TYPE_ONE_TIME,
            'day' => 2,
            'month' => 10,
            'year' => 2000,
        ]);
        $contactReminderBis = ContactReminder::factory()->create([
            'contact_id' => $mitchell->id,
            'type' => ContactReminder::TYPE_ONE_TIME,
            'day' => 2,
            'month' => 10,
            'year' => 2000,
        ]);
        DB::table('contact_reminder_scheduled')->insertGetId([
            'user_notification_channel_id' => $channel->id,
            'contact_reminder_id' => $contactReminder->id,
            'scheduled_at' => Carbon::now()->addDays(10),
            'triggered_at' => null,
        ]);
        DB::table('contact_reminder_scheduled')->insertGetId([
            'user_notification_channel_id' => $channel->id,
            'contact_reminder_id' => $contactReminderBis->id,
            'scheduled_at' => Carbon::now()->addDays(32),
            'triggered_at' => null,
        ]);

        $collection = VaultReminderIndexViewHelper::data($vault, $user);

        $this->assertCount(12, $collection->toArray());
        $this->assertEquals(
            0,
            $collection->toArray()[0]['id']
        );
        $this->assertEquals(
            'Jan 2022',
            $collection->toArray()[0]['month']
        );
        $this->assertEquals(
            [
                0 => [
                    'id' => $contactReminder->id,
                    'label' => $contactReminder->label,
                    'scheduled_at' => 'Jan 11, 2022',
                    'contact' => [
                        'id' => $mitchell->id,
                        'name' => $mitchell->name,
                        'avatar' => '123',
                        'url' => [
                            'show' => env('APP_URL').'/vaults/'.$vault->id.'/contacts/'.$mitchell->id,
                        ],
                    ],
                ],
            ],
            $collection->toArray()[0]['reminders']->toArray()
        );
    }
}
