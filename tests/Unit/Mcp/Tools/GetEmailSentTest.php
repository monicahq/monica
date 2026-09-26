<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\GetEmailSent;
use App\Models\EmailSent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetEmailSentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_one_email_with_its_copy(): void
    {
        $user = User::factory()->create();
        $email = EmailSent::factory()->create([
            'account_id' => $user->account_id,
            'body' => 'Somebody just signed in to your account from a device we have not seen before.',
        ]);

        EricaServer::actingAs($user)->tool(GetEmailSent::class, ['email_sent' => (string) $email->id])
            ->assertOk()
            ->assertSee('Somebody just signed in to your account from a device we have not seen before.');
    }

    #[Test]
    public function it_reports_an_email_of_another_account_as_not_existing(): void
    {
        $user = User::factory()->create();
        $email = EmailSent::factory()->create();

        EricaServer::actingAs($user)->tool(GetEmailSent::class, ['email_sent' => (string) $email->id])
            ->assertHasErrors(['That email does not exist.']);
    }

    #[Test]
    public function it_reports_an_identifier_that_is_not_a_number_as_not_existing(): void
    {
        $user = User::factory()->create();

        EricaServer::actingAs($user)->tool(GetEmailSent::class, ['email_sent' => '0'])
            ->assertHasErrors(['That email does not exist.']);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.emailsSent.show', app(GetEmailSent::class)->mirrors());
    }
}
