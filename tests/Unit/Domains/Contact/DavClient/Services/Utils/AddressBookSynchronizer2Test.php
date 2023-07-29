<?php

namespace Tests\Unit\Domains\Contact\DavClient\Services\Utils;

use App\Domains\Contact\DavClient\Jobs\GetMultipleVCard;
use App\Domains\Contact\DavClient\Services\Utils\AddressBookSynchronizer;
use App\Domains\Contact\DavClient\Services\Utils\PrepareJobsContactPush;
use App\Domains\Contact\DavClient\Services\Utils\PrepareJobsContactUpdater;
use App\Models\AddressBookSubscription;
use App\Models\Contact;
use App\Models\SyncToken;
use App\Models\Vault;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\RunClassInSeparateProcess;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Tests\Helpers\DavTester;
use Tests\TestCase;
use Tests\Unit\Domains\Contact\DAV\CardEtag;

#[RunClassInSeparateProcess]
#[RunTestsInSeparateProcesses]
class AddressBookSynchronizer2Test extends TestCase
{
    use DatabaseTransactions;
    use CardEtag;

    #[Test]
    public function it_sync_no_changes()
    {
        Bus::fake();

        $this->mock(PrepareJobsContactUpdater::class, function (MockInterface $mock) {
            $mock->shouldReceive('withSubscription')->once()->andReturn($mock);
            $mock->shouldReceive('execute')
                ->once()
                ->andReturn(collect());
        });
        $this->partialMock(PrepareJobsContactPush::class, function (MockInterface $mock) {
            $mock->shouldReceive('withSubscription')->once()->andReturn($mock);
            $mock->shouldReceive('execute')
                ->once()
                ->andReturn(collect());
        });

        $subscription = $this->getSubscription();

        $tester = (new DavTester($subscription->uri))
            ->getSynctoken('"test21"')
            ->getSyncCollection('"test20"')
            ->fake();

        (new AddressBookSynchronizer)
            ->withSubscription($subscription)
            ->execute();

        $tester->assert();
    }

    #[Test]
    public function it_sync_changes_added_local_contact()
    {
        Bus::fake();

        $subscription = $this->getSubscription();

        Contact::factory()->create([
            'vault_id' => $subscription->vault_id,
            'id' => 'd403af1c-8492-4e9b-9833-cf18c795dfa9',
        ]);

        $tester = (new DavTester($subscription->uri))
            ->getSynctoken('"token"')
            ->getSyncCollection('"token"', 'test2')
            ->fake();

        $this->mock(PrepareJobsContactUpdater::class, function (MockInterface $mock) {
            $mock->shouldReceive('withSubscription')->once()->andReturn($mock);
            $mock->shouldReceive('execute')
                ->once()
                ->withArgs(function ($contacts) {
                    $this->assertEquals('https://test/dav/addressbooks/user@test.com/contacts/uuid', $contacts->first()->uri);
                    $this->assertEquals('test2', $contacts->first()->etag);

                    return true;
                })
                ->andReturn(collect());
        });
        $this->partialMock(PrepareJobsContactPush::class, function (MockInterface $mock) {
            $mock->shouldReceive('withSubscription')->once()->andReturn($mock);
            $mock->shouldReceive('execute')
                ->once()
                ->withArgs(function ($localChanges, $changes) {
                    $this->assertEquals('test2', $changes->first()->etag);

                    return true;
                })
                ->andReturn(collect());
        });

        (new AddressBookSynchronizer)
            ->withSubscription($subscription)
            ->execute();

        $tester->assert();
    }

    #[Test]
    public function it_sync_changes_added_local_contact_batched()
    {
        Bus::fake();

        $subscription = $this->getSubscription();

        Contact::factory()->create([
            'vault_id' => $subscription->vault_id,
            'id' => 'd403af1c-8492-4e9b-9833-cf18c795dfa9',
        ]);

        $tester = (new DavTester($subscription->uri))
            ->getSynctoken('"token"')
            ->getSyncCollection('"token"', 'test2')
            ->fake();

        (new AddressBookSynchronizer)
            ->withSubscription($subscription)
            ->execute();

        $tester->assert();

        Bus::assertBatched(function (PendingBatch $batch) {
            $this->assertCount(1, $batch->jobs);
            $job = $batch->jobs[0];
            $this->assertInstanceOf(GetMultipleVCard::class, $job);
            $this->assertEquals(['https://test/dav/addressbooks/user@test.com/contacts/uuid'], $this->getPrivateValue($job, 'hrefs'));

            return true;
        });
    }

    private function getSubscription(): AddressBookSubscription
    {
        $subscription = AddressBookSubscription::factory()->create([
            'uri' => 'https://test/dav/addressbooks/user@test.com/contacts/',
        ]);
        $this->setPermissionInVault($subscription->user, Vault::PERMISSION_VIEW, $subscription->vault);
        $token = SyncToken::factory()->create([
            'account_id' => $subscription->user->account_id,
            'user_id' => $subscription->user_id,
            'name' => 'contacts1',
            'timestamp' => now()->addDays(-1),
        ]);
        $subscription->sync_token_id = $token->id;
        $subscription->save();

        $this->assertDatabaseHas('addressbook_subscriptions', [
            'id' => $subscription->id,
            'sync_token_id' => $token->id,
        ]);

        return $subscription;
    }
}
