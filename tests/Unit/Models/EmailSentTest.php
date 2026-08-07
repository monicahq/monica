<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\EmailSent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailSentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $account = Account::factory()->create();
        $emailSent = EmailSent::factory()->create(['account_id' => $account->id]);

        $this->assertTrue($emailSent->account()->exists());
        $this->assertTrue($account->is($emailSent->account));
    }

    #[Test]
    public function it_belongs_to_a_user(): void
    {
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        $emailSent = EmailSent::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ]);

        $this->assertTrue($emailSent->user()->exists());
        $this->assertEquals('michael.scott@dundermifflin.com', $emailSent->user->email);
    }

    #[Test]
    public function it_has_no_user_when_the_system_emailed_someone_without_an_account(): void
    {
        $emailSent = EmailSent::factory()->withoutUser()->create();

        $this->assertFalse($emailSent->user()->exists());
        $this->assertNull($emailSent->user);
    }
}
