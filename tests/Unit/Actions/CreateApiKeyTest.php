<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateApiKey;
use App\Enums\ApiKeyScopeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateApiKeyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_mints_a_key_for_the_user(): void
    {
        Queue::fake();

        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        $key = new CreateApiKey(user: $user, name: 'Dundie awards bot')->execute();

        $this->assertInstanceOf(NewAccessToken::class, $key);
        $this->assertSame(1, $user->tokens()->count());
        $this->assertSame('Dundie awards bot', $user->tokens()->first()->name);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'Dundie awards bot',
        ]);
    }

    #[Test]
    public function it_returns_the_key_in_plain_text_and_stores_only_its_hash(): void
    {
        Queue::fake();

        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        $key = new CreateApiKey(user: $user, name: 'Deployment script')->execute()->plainTextToken;

        $stored = $user->tokens()->first()->token;

        $this->assertNotSame($key, $stored);
        $this->assertSame(hash('sha256', $key), $stored);
    }

    #[Test]
    public function it_returns_the_row_it_wrote(): void
    {
        Queue::fake();

        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        $key = new CreateApiKey(user: $user, name: 'Dundie awards bot')->execute();

        $this->assertInstanceOf(PersonalAccessToken::class, $key->accessToken);
        $this->assertSame($user->tokens()->first()->id, $key->accessToken->id);
        $this->assertSame('Dundie awards bot', $key->accessToken->name);
    }

    #[Test]
    public function it_leaves_a_key_unused_until_something_uses_it(): void
    {
        Queue::fake();

        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        new CreateApiKey(user: $user, name: 'Deployment script')->execute();

        $this->assertNull($user->tokens()->first()->last_used_at);
    }

    #[Test]
    public function it_logs_the_creation(): void
    {
        Queue::fake();

        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        new CreateApiKey(user: $user, name: 'Dundie awards bot')->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ApiKeyCreation
                && $job->account->id === $account->id
                && $job->user->id === $user->id
                && $job->parameters === ['name' => 'Dundie awards bot', 'scopes' => []],
        );
    }

    #[Test]
    public function it_mints_a_key_reaching_exactly_the_scopes_it_was_handed(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new CreateApiKey(
            user: $user,
            name: 'Dwight’s phone',
            scopes: ['*', ApiKeyScopeEnum::SensitiveFields->value],
        )->execute();

        $this->assertEquals(
            ['*', 'sensitive-fields'],
            $user->tokens()->first()->abilities,
        );
    }

    #[Test]
    public function it_mints_a_key_reaching_nothing_it_was_not_handed(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new CreateApiKey(user: $user, name: 'Deployment script', scopes: ['*'])->execute();

        $token = $user->tokens()->first();

        $this->assertEquals(['*'], $token->abilities);
        $this->assertFalse(in_array(ApiKeyScopeEnum::SensitiveFields->value, $token->abilities, true));
    }
}
