<?php

namespace Tests\Commands\Other;

use Tests\TestCase;
use App\Models\User\User;
use Mockery\MockInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\DavClient\CreateAddressBookSubscription;

class CreateAddressBookSubscriptionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_add_addressbook()
    {
        $user = factory(User::class)->create();

        $this->mock(CreateAddressBookSubscription::class, function (MockInterface $mock) use ($user) {
            $mock->shouldReceive('execute')
                ->once()
                ->withArgs(function ($data) use ($user) {
                    $this->assertEquals([
                        'account_id' => $user->account_id,
                        'user_id' => $user->id,
                        'base_uri' => 'https://test',
                        'username' => 'login',
                        'password' => 'password',
                    ], $data);

                    return true;
                });
        });

        $this->artisan('monica:newaddressbooksubscription', [
            '--email' => $user->email,
            '--url' => 'https://test',
            '--login' => 'login',
            '--password' => 'password',
        ])->run();
    }
}
