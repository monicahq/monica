<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
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

        MonicaServer::actingAs($user)->tool(GetEmailSent::class, ['email_sent' => (string) $email->id])
            ->assertOk()
            ->assertSee('Somebody just signed in to your account from a device we have not seen before.');
    }

    #[Test]
    public function it_reports_an_email_of_another_account_as_not_existing(): void
    {
        $user = User::factory()->create();
        $email = EmailSent::factory()->create();

        MonicaServer::actingAs($user)->tool(GetEmailSent::class, ['email_sent' => (string) $email->id])
            ->assertHasErrors();
    }
}
