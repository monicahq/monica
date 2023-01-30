<?php

namespace Tests\Unit\Services\DavClient;

use Tests\TestCase;
use Mockery\MockInterface;
use App\Models\User\SyncToken;
use App\Models\Account\AddressBookSubscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Http\Controllers\DAV\Backend\CardDAV\CardDAVBackend;
use App\Services\DavClient\UpdateSubscriptionLocalSyncToken;

class UpdateSubscriptionLocalSyncTokenTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_update_token()
    {
        $subscription = AddressBookSubscription::factory()->create([
            'name' => 'contacts1',
        ]);
        $token = factory(SyncToken::class)->create([
            'account_id' => $subscription->account_id,
            'user_id' => $subscription->user_id,
            'name' => 'contacts1',
            'timestamp' => now()->addDays(-1),
        ]);

        $this->mock(CardDAVBackend::class, function (MockInterface $mock) use ($token) {
            $mock->shouldReceive('init')->andReturn($mock);
            $mock->shouldReceive('getCurrentSyncToken')
                ->withArgs(function ($name) {
                    $this->assertEquals($name, 'contacts1');

                    return true;
                })
                ->andReturn($token);
        });

        (new UpdateSubscriptionLocalSyncToken())->execute([
            'account_id' => $subscription->account_id,
            'addressbook_subscription_id' => $subscription->id,
        ]);

        $subscription->refresh();

        $this->assertEquals($token->id, $subscription->localSyncToken);
    }

    /** @test */
    public function it_wont_update_null_token()
    {
        $subscription = AddressBookSubscription::factory()->create([
            'name' => 'contacts1',
        ]);

        $this->mock(CardDAVBackend::class, function (MockInterface $mock) {
            $mock->shouldReceive('init')->andReturn($mock);
            $mock->shouldReceive('getCurrentSyncToken')
                ->withArgs(function ($name) {
                    $this->assertEquals($name, 'contacts1');

                    return true;
                })
                ->andReturn(null);
        });

        (new UpdateSubscriptionLocalSyncToken())->execute([
            'account_id' => $subscription->account_id,
            'addressbook_subscription_id' => $subscription->id,
        ]);

        $subscription->refresh();

        $this->assertNull($subscription->localSyncToken);
    }
}
