<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\ListEmailsSent;
use App\Models\EmailSent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListEmailsSentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_emails_sent_to_the_account_newest_first(): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->create(['account_id' => $user->account_id, 'subject' => 'Your sign-in link', 'sent_at' => now()->subDay()]);
        EmailSent::factory()->create(['account_id' => $user->account_id, 'subject' => 'A new sign-in on your account', 'sent_at' => now()]);

        EricaServer::actingAs($user)->tool(ListEmailsSent::class, ['per_page' => 1])
            ->assertOk()
            ->assertSee('A new sign-in on your account')
            ->assertDontSee('Your sign-in link')
            ->assertSee('"total":2');
    }

    #[Test]
    public function it_leaves_the_copy_of_each_email_out(): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->create(['account_id' => $user->account_id, 'body' => 'Somebody just signed in to your account.']);

        EricaServer::actingAs($user)->tool(ListEmailsSent::class)
            ->assertOk()
            ->assertDontSee('Somebody just signed in to your account.');
    }

    #[Test]
    public function it_leaves_out_the_emails_of_another_account(): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->create(['account_id' => $user->account_id, 'email_address' => 'michael.scott@dundermifflin.com']);
        EmailSent::factory()->create(['email_address' => 'david.wallace@dundermifflin.com']);

        EricaServer::actingAs($user)->tool(ListEmailsSent::class)
            ->assertOk()
            ->assertSee('michael.scott@dundermifflin.com')
            ->assertDontSee('david.wallace@dundermifflin.com');
    }

    #[Test]
    public function it_answers_the_page_that_was_asked_for(): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->create(['account_id' => $user->account_id, 'subject' => 'Monday', 'sent_at' => now()->subDays(2)]);
        EmailSent::factory()->create(['account_id' => $user->account_id, 'subject' => 'Tuesday', 'sent_at' => now()->subDay()]);
        EmailSent::factory()->create(['account_id' => $user->account_id, 'subject' => 'Wednesday', 'sent_at' => now()]);

        EricaServer::actingAs($user)->tool(ListEmailsSent::class, ['page' => 2, 'per_page' => 2])
            ->assertOk()
            ->assertSee('Monday')
            ->assertDontSee('Wednesday')
            ->assertSee('"page":2');
    }
}
