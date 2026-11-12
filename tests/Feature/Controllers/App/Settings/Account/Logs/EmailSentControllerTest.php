<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Logs;

use App\Models\Account;
use App\Models\EmailSent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailSentControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_emails_sent_screen(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create([
            'account_id' => $account->id,
            'first_name' => 'Pam',
            'last_name' => 'Beesly',
        ]);
        EmailSent::factory()->create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'email_address' => 'pam.beesly@dundermifflin.com',
            'subject' => 'A sign-in from a new place',
        ]);

        $response = $this->actingAs($user)->get(route('settings.emailsSent.index'));

        $response->assertStatus(200);
        $response->assertSee('pam.beesly@dundermifflin.com', escape: false);
        $response->assertSee('A sign-in from a new place', escape: false);
        $response->assertSee(route('settings.logs.index'), escape: false);
    }

    #[Test]
    public function it_badges_an_email_that_is_still_on_its_way(): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('settings.emailsSent.index'));

        $response->assertStatus(200);
    }

    /** @return array<string, array{string, string}> */
    public static function deliveryOutcomes(): array
    {
        return [
            'delivered' => ['delivered', 'Delivered'],
            'bounced' => ['bounced', 'Bounced'],
            'complained' => ['complained', 'Marked as spam'],
        ];
    }

    #[Test]
    #[DataProvider('deliveryOutcomes')]
    public function it_badges_an_email_by_what_the_provider_reported(string $state, string $badge): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->{$state}()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('settings.emailsSent.index'));

        $response->assertStatus(200);
        $response->assertSee($badge, escape: false);
        $response->assertDontSee('On its way', escape: false);
    }
}
