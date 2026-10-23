<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateSecurityKey;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\SecurityKey;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateSecurityKeyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renames_a_key(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $key = SecurityKey::factory()->for($michael)->create();

        new UpdateSecurityKey(user: $michael, securityKey: $key, name: 'Dundie key')->execute();

        $this->assertDatabaseHas('security_keys', ['id' => $key->id, 'name' => 'Dundie key']);
        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::SecurityKeyRenamed
            && $job->parameters === ['name' => 'Dundie key']);
    }

    #[Test]
    public function it_refuses_a_key_belonging_to_somebody_else(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new UpdateSecurityKey(
            user: User::factory()->create(),
            securityKey: SecurityKey::factory()->create(),
            name: 'Dundie key',
        )->execute();
    }
}
