<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateApiKey;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateApiKeyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_mints_a_key_for_the_user(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $key = new CreateApiKey(user: $user, name: 'Dundie awards bot')->execute();

        $this->assertIsString($key);
        $this->assertSame(1, $user->tokens()->count());
        $this->assertSame('Dundie awards bot', $user->tokens()->first()->name);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'Dundie awards bot',
        ]);
    }

    /**
     * The key comes back whole, while only its hash is written down, so a lost
     * key is replaced rather than read back out of the table.
     */
    #[Test]
    public function it_returns_the_key_in_plain_text_and_stores_only_its_hash(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $key = new CreateApiKey(user: $user, name: 'Deployment script')->execute();

        $this->assertStringContainsString('|', $key);

        $stored = $user->tokens()->first()->token;

        $this->assertNotSame($key, $stored);
        $this->assertSame(hash('sha256', explode('|', $key)[1]), $stored);
    }

    #[Test]
    public function it_leaves_a_key_unused_until_something_uses_it(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        new CreateApiKey(user: $user, name: 'Deployment script')->execute();

        $this->assertNull($user->tokens()->first()->last_used_at);
    }

    #[Test]
    public function it_logs_the_creation(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        new CreateApiKey(user: $user, name: 'Dundie awards bot')->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ApiKeyCreation
                && $job->company->id === $company->id
                && $job->user->id === $user->id
                && $job->parameters === ['name' => 'Dundie awards bot'],
        );
    }
}
