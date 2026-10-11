<?php

declare(strict_types=1);

namespace Tests\Unit\Features;

use App\Actions\DeactivateFlagForEveryAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShippedStateTest extends TestCase
{
    use RefreshDatabase;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Feature::discover();
    }

    /** @return array<string, array{0: string}> */
    public static function withdrawnFlags(): array
    {
        return [
            'carddav' => ['carddav'],
            'file-upload' => ['file-upload'],
            'sms-notifications' => ['sms-notifications'],
            'vcard-export' => ['vcard-export'],
        ];
    }

    #[Test]
    #[DataProvider('withdrawnFlags')]
    public function it_ships_a_withdrawn_capability_switched_off_for_every_account(string $flag): void
    {
        $jim = User::factory()->create();

        $this->assertFalse($jim->account->features()->active($flag));
        $this->assertFalse(Feature::for(null)->active($flag));
    }

    #[Test]
    public function it_ships_the_mcp_server_switched_on_for_every_account(): void
    {
        $pam = User::factory()->create();

        $this->assertTrue($pam->account->features()->active('mcp-server'));
        $this->assertTrue(Feature::for(null)->active('mcp-server'));
    }

    #[Test]
    public function it_withdraws_the_mcp_server_from_every_account_once_switched_off(): void
    {
        $pam = User::factory()->create();
        $pam->account->features()->active('mcp-server');

        new DeactivateFlagForEveryAccount(flag: 'mcp-server')->execute();
        Feature::flushCache();

        $dwight = User::factory()->create();

        $this->assertFalse($pam->account->features()->active('mcp-server'));
        $this->assertFalse($dwight->account->features()->active('mcp-server'));
        $this->assertFalse(Feature::for(null)->active('mcp-server'));
    }
}
