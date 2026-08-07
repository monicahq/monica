<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Log;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogUserActionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_logs_the_action_of_the_user(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        new LogUserAction(
            company: $company,
            user: $user,
            action: UserActionEnum::CompanyUpdate,
            parameters: ['name' => 'Dunder Mifflin'],
        )->handle();

        $this->assertDatabaseHas('logs', [
            'company_id' => $company->id,
            'user_id' => $user->id,
            'user_email' => 'michael.scott@dundermifflin.com',
            'action' => UserActionEnum::CompanyUpdate->value,
        ]);

        $this->assertEquals(['name' => 'Dunder Mifflin'], Log::query()->latest()->first()->parameters);
    }

    #[Test]
    public function it_keeps_the_email_of_the_user_once_they_are_deleted(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        new LogUserAction(
            company: $company,
            user: $user,
            action: UserActionEnum::UserPasswordUpdate,
        )->handle();

        $user->forceDelete();

        $log = Log::query()->latest()->first();

        $this->assertNull($log->user_id);
        $this->assertEquals('michael.scott@dundermifflin.com', $log->user_email);
    }
}
