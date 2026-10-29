<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Security;

use App\Models\User;
use App\ViewModels\Settings\Account\Security\SessionListViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SessionListViewModelTest extends TestCase
{
    use RefreshDatabase;

    private const string CHROME_ON_MACOS = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36';

    private const string SAFARI_ON_IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1';

    #[Test]
    public function it_lists_the_device_in_hand_first_and_marks_it(): void
    {
        $user = User::factory()->create();
        $this->insertSession(id: 'scranton', user: $user, userAgent: self::CHROME_ON_MACOS, address: '10.0.0.1', minutesAgo: 30);
        $this->insertSession(id: 'stamford', user: $user, userAgent: self::SAFARI_ON_IPHONE, address: '10.0.0.2', minutesAgo: 1);

        $sessions = new SessionListViewModel(user: $user, currentSessionId: 'scranton')->sessions();

        $this->assertSame([
            [
                'id' => 'scranton',
                'device' => 'Chrome on macOS',
                'address' => '10.0.0.1',
                'lastUsedAt' => '30 minutes ago',
                'isCurrent' => true,
            ],
            [
                'id' => 'stamford',
                'device' => 'Safari on iPhone',
                'address' => '10.0.0.2',
                'lastUsedAt' => '1 minute ago',
                'isCurrent' => false,
            ],
        ], $sessions);
    }

    #[Test]
    public function it_names_a_device_that_announced_nothing_unknown(): void
    {
        $user = User::factory()->create();
        $this->insertSession(id: 'nashua', user: $user, userAgent: null, address: null, minutesAgo: 5);

        $sessions = new SessionListViewModel(user: $user, currentSessionId: 'scranton')->sessions();

        $this->assertSame('Unknown device', $sessions[0]['device']);
        $this->assertNull($sessions[0]['address']);
    }

    #[Test]
    public function it_leaves_out_a_session_that_has_run_out(): void
    {
        config(['session.lifetime' => 120]);
        $user = User::factory()->create();
        $this->insertSession(id: 'scranton', user: $user, userAgent: self::CHROME_ON_MACOS, address: '10.0.0.1', minutesAgo: 5);
        $this->insertSession(id: 'utica', user: $user, userAgent: self::CHROME_ON_MACOS, address: '10.0.0.3', minutesAgo: 121);

        $sessions = new SessionListViewModel(user: $user, currentSessionId: 'scranton')->sessions();

        $this->assertSame(['scranton'], array_column($sessions, 'id'));
    }

    #[Test]
    public function it_leaves_out_the_sessions_of_another_user(): void
    {
        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $this->insertSession(id: 'scranton', user: $michael, userAgent: self::CHROME_ON_MACOS, address: '10.0.0.1', minutesAgo: 5);
        $this->insertSession(id: 'schrute-farms', user: $dwight, userAgent: self::CHROME_ON_MACOS, address: '10.0.0.4', minutesAgo: 5);

        $sessions = new SessionListViewModel(user: $michael, currentSessionId: 'scranton')->sessions();

        $this->assertSame(['scranton'], array_column($sessions, 'id'));
    }

    #[Test]
    public function it_cannot_list_sessions_kept_outside_the_database(): void
    {
        $user = User::factory()->create();

        config(['session.driver' => 'file']);
        $this->assertFalse(new SessionListViewModel(user: $user, currentSessionId: 'scranton')->canList());

        config(['session.driver' => 'database']);
        $this->assertTrue(new SessionListViewModel(user: $user, currentSessionId: 'scranton')->canList());
    }

    #[Test]
    public function it_counts_the_devices_in_its_header(): void
    {
        $user = User::factory()->create();
        $this->insertSession(id: 'scranton', user: $user, userAgent: self::CHROME_ON_MACOS, address: '10.0.0.1', minutesAgo: 5);

        $this->assertSame('Signed in on 1 device', new SessionListViewModel(user: $user, currentSessionId: 'scranton')->header());

        $this->insertSession(id: 'stamford', user: $user, userAgent: self::SAFARI_ON_IPHONE, address: '10.0.0.2', minutesAgo: 1);

        $this->assertSame('Signed in on 2 devices', new SessionListViewModel(user: $user, currentSessionId: 'scranton')->header());
    }

    private function insertSession(string $id, User $user, ?string $userAgent, ?string $address, int $minutesAgo): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => $address,
            'user_agent' => $userAgent,
            'payload' => '',
            'last_activity' => now()->subMinutes($minutesAgo)->getTimestamp(),
        ]);
    }
}
