<?php

namespace Tests\Unit\Domains\Contact\DavClient\Services;

use App\Domains\Contact\Dav\Web\Backend\CardDAV\CardDAVBackend;
use App\Domains\Contact\DavClient\Services\UpdateSubscriptionLocalSyncToken;
use App\Models\AddressBookSubscription;
use App\Models\SyncToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery\MockInterface;
use Tests\TestCase;

class UpdateSubscriptionLocalSyncTokenTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_update_token()
    {
        $subscription = AddressBookSubscription::factory()->create();
        $token = SyncToken::factory()->create([
            'account_id' => $subscription->user->account_id,
            'user_id' => $subscription->user_id,
            'name' => 'contacts1',
            'timestamp' => now()->addDays(-1),
        ]);

        $this->mock(CardDAVBackend::class, function (MockInterface $mock) use ($token, $subscription) {
            $mock->shouldReceive('withUser')->andReturnSelf();
            $mock->shouldReceive('getCurrentSyncToken')
                ->withArgs(function ($id) use ($subscription) {
                    $this->assertEquals($id, $subscription->vault_id);

                    return true;
                })
                ->andReturn($token);
        });

        (new UpdateSubscriptionLocalSyncToken)->execute([
            'account_id' => $subscription->user->account_id,
            'addressbook_subscription_id' => $subscription->id,
        ]);

        $subscription->refresh();

        $this->assertEquals($token->id, $subscription->sync_token_id);
    }

    /** @test */
    public function it_wont_update_null_token()
    {
        $subscription = AddressBookSubscription::factory()->create();

        $this->mock(CardDAVBackend::class, function (MockInterface $mock) use ($subscription) {
            $mock->shouldReceive('withUser')->andReturnSelf();
            $mock->shouldReceive('getCurrentSyncToken')
                ->withArgs(function ($id) use ($subscription) {
                    $this->assertEquals($id, $subscription->vault_id);

                    return true;
                })
                ->andReturn(null);
        });

        (new UpdateSubscriptionLocalSyncToken)->execute([
            'account_id' => $subscription->user->account_id,
            'addressbook_subscription_id' => $subscription->id,
        ]);

        $subscription->refresh();

        $this->assertNull($subscription->sync_token_id);
    }
}
