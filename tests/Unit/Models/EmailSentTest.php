<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\EmailSent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailSentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_company(): void
    {
        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        $emailSent = EmailSent::factory()->create(['company_id' => $company->id]);

        $this->assertTrue($emailSent->company()->exists());
        $this->assertEquals('Dunder Mifflin', $emailSent->company->name);
    }

    #[Test]
    public function it_belongs_to_a_user(): void
    {
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        $emailSent = EmailSent::factory()->create([
            'company_id' => $user->company_id,
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
