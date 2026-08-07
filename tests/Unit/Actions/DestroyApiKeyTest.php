<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyApiKey;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyApiKeyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_revokes_the_key(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $apiKey = $user->createToken('Dundie awards bot')->accessToken;

        new DestroyApiKey(user: $user, apiKeyId: $apiKey->id)->execute();

        $this->assertModelMissing($apiKey);
        $this->assertSame(0, $user->tokens()->count());
    }

    #[Test]
    public function it_refuses_a_key_that_belongs_to_somebody_else(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $michael = User::factory()->create(['company_id' => $company->id]);
        $dwight = User::factory()->create(['company_id' => $company->id]);
        $apiKey = $dwight->createToken('Beet farm sync')->accessToken;

        $this->expectException(ModelNotFoundException::class);

        try {
            new DestroyApiKey(user: $michael, apiKeyId: $apiKey->id)->execute();
        } finally {
            $this->assertModelExists($apiKey);
        }
    }

    #[Test]
    public function it_logs_the_revocation_with_the_name_the_key_had(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $apiKey = $user->createToken('Dundie awards bot')->accessToken;

        new DestroyApiKey(user: $user, apiKeyId: $apiKey->id)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ApiKeyDeletion
                && $job->company->id === $company->id
                && $job->user->id === $user->id
                && $job->parameters === ['name' => 'Dundie awards bot'],
        );
    }
}
