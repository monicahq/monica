<?php

namespace Tests\Unit\Domains\Vault\ManageVault\Web\ViewHelpers;

use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultReminderIndexViewHelper;
use App\Models\Contact;
use App\Models\ContactReminder;
use App\Models\User;
use App\Models\UserNotificationChannel;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

use function env;

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
                        'avatar' => [
                            'type' => Contact::AVATAR_TYPE_SVG,
                            'content' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 231 231"><path d="M33.83,33.83a115.5,115.5,0,1,1,0,163.34,115.49,115.49,0,0,1,0-163.34Z" style="fill:#fc0065;"/><path d="m115.5 51.75a63.75 63.75 0 0 0-10.5 126.63v14.09a115.5 115.5 0 0 0-53.729 19.027 115.5 115.5 0 0 0 128.46 0 115.5 115.5 0 0 0-53.729-19.029v-14.084a63.75 63.75 0 0 0 53.25-62.881 63.75 63.75 0 0 0-63.65-63.75 63.75 63.75 0 0 0-0.09961 0z" style="fill:#fff;"/><path d="M61.11,205.59l3.49,3.69-6.26,6.6A115.45,115.45,0,0,0,72,222.51v-22a115.19,115.19,0,0,0-10.85,5.1Z" style="fill:#e000cb;"/><path d="M93.24,228.85V199l-4-4A114.43,114.43,0,0,0,72,200.49v22a114.43,114.43,0,0,0,21.28,6.34Z" style="fill:#fff;"/><path d="m159 222.51v-22a114.63 114.63 0 0 0-17.25-5.51l-4 4v29.86a114.16 114.16 0 0 0 21.25-6.35z" style="fill:#fff;"/><path d="m169.89 205.59-3.49 3.69 6.26 6.6a115.45 115.45 0 0 1-13.66 6.63v-22a115.19 115.19 0 0 1 10.85 5.1z" style="fill:#e000cb;"/><path d="M115.5,219.62A28.5,28.5,0,0,1,87.25,195c2.93-.74,5.92-1.36,8.94-1.87a19.41,19.41,0,0,0,38.62,0c3,.51,6,1.13,8.94,1.87a28.49,28.49,0,0,1-28.25,24.63Z" style="fill:#ffce1c;"/><path d="m156.1 15.879c-0.38556 5.3015-1.7049 9.4762-3.6602 12.76-0.41226 23.773-9.2343 35.229-15.154 42.797l15.062-4.6641c-0.66253 2.8135-2.4628 7.156-0.34766 12.137 1.6334-2.3144 7.9395-5.807 13-3.3477-0.43442 3.5532-0.95271 7.094-1.4512 10.639l8.9648 0.85937c0.83453 3.8792 0.51719 9.3449-0.59961 11.736l5.5508 2.0098c0.20764 2.7646 0.10001 5.4906-0.74609 8.875 8.4545-1.7225 14.213-4.3896 19.641-13.188 2.8639-4.7524 4.9018-10.483 4.7305-17.242-4.1612 4.916-9.6484 7.2485-15.26 10.109 6.507-11.065 8.8648-22.768 8.1367-30.58-7.3456 10.251-11.649 13.06-19.918 16.9 1.2386-11.4 5.5249-18.582 12.461-27.27-11.392-1.3025-16.301 1.4749-24.891 6.4395 4.5466-14.036 2.2208-26.679-5.5195-38.971zm-117.76 28.682c9.3378 3.6366 19.581 9.0234 21.129 18.549-7.6182 0.0414-14.897-3.5072-20.242-7.1894-0.15967 8.2309 2.8451 12.252 6.7734 19.08-7.2127 1.6129-12.084 4.8315-17.471 9.4805 7.2948-0.15715 12.299-1.0502 16.891 4.2793-6.0512 5.0164-11.99 10.79-11.99 19.24 9.257-6.1688 12.495-5.9486 21.137-2.2012 1.2906-8.0996 2.3978-14.872 2.7869-16.435 2.4719-0.73247 3.5247-0.94807 5.9221-1.2938-2.1556-7.4281 1.0996-9.5176 2.4141-11.6l7.543 1.5059c-3.9093-6.1699 2.6565-12.483 7.1445-15.51-4.4474-7.2082-5.6649-11.558-7.377-16.797-11.198-8.2947-23.895-6.2742-34.66-1.1094z" style="fill:#ffc;"/><path d="m101.9 7.6408c-10.047 6.2416-12.441 28.646-12.131 33.289-6.9249-5.8258-7.8992-13.75-7.7695-19.203-9.6235 6.0158-10.666 14.421-9 23.943 1.1061 5.1411 2.3972 10.461 7.377 16.797 2e-3 -1e-3 4e-3 -3e-3 6e-3 -4e-3 2.7742 2.8742 5.4644 5.5941 8.3477 8.3574 0.41187-6.971 0.45449-13.622 7.1856-15.824 3.9532 2.8169 7.4123 5.9388 11.084 9.1035l10.559-10.25c5.6447 3.961 5.4531 6.5652 6.5215 14.104 2.153-1.7546 8.719-9.0037 15.844-10.139 0.98706 4.1261-0.99388 10.308-2.6387 13.621 0 0 14.32-11.846 15.195-27.971 0.33968-6.2599 0.2237-11.146-0.041-14.826-3.2125 5.5652-8.7118 8.7799-13.789 10.15-4.2715-9.2486-2.4785-21.435-0.48047-29.309-12.21 3.0195-20.932 18.337-22.172 25.07-9.2678-7.397-13.605-16.146-14.098-26.91z" style="fill:#ffc;"/><path d="m70.959 94.985h35.031c2.4086 1e-5 4.3612 1.9523 4.3612 4.3606l-2.5864 17.511c-0.3515 2.3799-1.7218 4.3606-3.8457 4.3606h-30.9c-2.1239-1e-5 -3.8457-1.9523-3.8457-4.3606l-2.5864-17.511c1e-5 -2.4082 1.9526-4.3606 4.3612-4.3606z" style="fill:#9ff3ff;opacity:0.96;stroke-linecap:round;stroke-linejoin:round;stroke-width:3.0045px;stroke:#000;"/><path d="m160.05 94.985h-35.031c-2.4086 1e-5 -4.3612 1.9523-4.3612 4.3606l2.5864 17.511c0.35149 2.3799 1.7218 4.3606 3.8457 4.3606h30.9c2.1239-1e-5 3.8457-1.9523 3.8457-4.3606l2.5864-17.511c-1e-5 -2.4082-1.9526-4.3606-4.3612-4.3606z" style="fill:#9ff3ff;opacity:0.96;stroke-linecap:round;stroke-linejoin:round;stroke-width:3.0045px;stroke:#000;"/><path d="m90.607 102.35a4.6337 4.6332 0 1 0 4.6892 4.6337 4.6337 4.6332 0 0 0-4.6892-4.6337zm49.72 0a4.6337 4.6332 0 1 0 4.6444 4.6337 4.6337 4.6332 0 0 0-4.6444-4.6337z" style="fill:#2f508a;"/><path d="m70.66 94.985h-11.775" style="fill:none;stroke-linecap:round;stroke-linejoin:round;stroke-width:3.0045px;stroke:#000;"/><path d="m172.13 94.985h-19.484" style="fill:none;stroke-linecap:round;stroke-linejoin:round;stroke-width:3.0045px;stroke:#000;"/><path d="m109.32 106.2c4.2045-2.427 9.3036-1.913 12.353-0.0258" style="fill:none;stroke-linecap:round;stroke-linejoin:round;stroke-width:3.0045px;stroke:#000;"/><path d="m148.33 109.79-5.7626-8.2324" style="fill:none;stroke-linecap:round;stroke-linejoin:round;stroke-width:4;stroke:none;"/><path d="m156.27 105-2.403-3.4328" style="fill:none;stroke-linecap:round;stroke-linejoin:round;stroke-width:4;stroke:none;"/><path d="m82.748 114.34-8.9489-12.784" style="fill:none;stroke-linecap:round;stroke-linejoin:round;stroke-width:4;stroke:none;"/><path d="m91.408 109.79-5.7626-8.2324" style="fill:none;stroke-linecap:round;stroke-linejoin:round;stroke-width:4;stroke:none;"/><path d="m115.5 161.71c-8.24 0-14.46-4.15-19.19-11.25 3.37-2.44 6.51-4.57 10-6.79a5.25 5.25 0 0 1 5.48-0.17 28.19 28.19 0 0 1 3.68 2.75 28.19 28.19 0 0 1 3.68-2.75 5.25 5.25 0 0 1 5.48 0.17c3.52 2.22 6.66 4.35 10 6.79-4.74 7.1-11 11.25-19.19 11.25z" style="fill:#000;"/></svg>',
                        ],
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
