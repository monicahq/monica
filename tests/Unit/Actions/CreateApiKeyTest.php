<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateApiKey;
use App\Enums\ApiKeyClientEnum;
use App\Enums\ApiKeyScopeEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ScopeIsNotGrantable;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\NewAccessToken;
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

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ApiKeyCreation
                && $job->account->id === $account->id
                && $job->user->id === $user->id
                && $job->parameters === ['name' => 'Dundie awards bot', 'scopes' => [], 'client' => 'script'],
        );
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

    #[Test]
    public function it_records_the_kind_of_caller_the_key_was_minted_for(): void
    {
        $user = User::factory()->create();

        $key = new CreateApiKey(
            user: $user,
            name: 'Dundie awards bot',
            client: ApiKeyClientEnum::Assistant,
        )->execute();

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $key->accessToken->id,
            'client' => ApiKeyClientEnum::Assistant->value,
        ]);
    }

    #[Test]
    public function it_treats_a_key_minted_without_a_kind_as_a_personal_script(): void
    {
        $user = User::factory()->create();

        $key = new CreateApiKey(user: $user, name: 'Deployment script')->execute();

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $key->accessToken->id,
            'client' => ApiKeyClientEnum::Script->value,
        ]);
    }

    #[Test]
    public function it_lets_an_administrator_mint_a_key_that_answers_support_requests(): void
    {
        $user = User::factory()->create();
        Administrator::factory()->create(['user_id' => $user->id]);

        $key = new CreateApiKey(user: $user, name: 'Support desk', scopes: ['*', 'administration'])->execute();

        $this->assertEquals(['*', 'administration'], $key->accessToken->abilities);
    }

    #[Test]
    public function it_refuses_the_administration_scope_to_somebody_who_does_not_run_the_instance(): void
    {
        $this->expectException(ScopeIsNotGrantable::class);

        new CreateApiKey(user: User::factory()->create(), name: 'Dwight’s backdoor', scopes: ['*', 'administration'])->execute();
    }

    #[Test]
    public function it_refuses_the_administration_scope_to_a_key_that_does_not_hold_it(): void
    {
        $user = User::factory()->create();
        Administrator::factory()->create(['user_id' => $user->id]);
        $user->withAccessToken($user->createToken('Michael’s phone', ['*'])->accessToken);

        $this->expectException(ScopeIsNotGrantable::class);

        new CreateApiKey(user: $user, name: 'Support desk', scopes: ['*', 'administration'])->execute();
    }

    #[Test]
    public function it_lets_a_key_holding_the_administration_scope_give_it_to_another_key(): void
    {
        $user = User::factory()->create();
        Administrator::factory()->create(['user_id' => $user->id]);
        $user->withAccessToken($user->createToken('Support desk', ['*', 'administration'])->accessToken);

        $key = new CreateApiKey(user: $user, name: 'Second support desk', scopes: ['*', 'administration'])->execute();

        $this->assertEquals(['*', 'administration'], $key->accessToken->abilities);
    }
}
