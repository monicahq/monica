<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyPossessionLocationStay;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Possession;
use App\Models\PossessionLocationStay;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyPossessionLocationStayTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function deleting_the_open_stay_clears_the_current_location(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $statue = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Ceramic cat']);
        $past = PossessionLocationStay::factory()->create(['possession_id' => $statue->id, 'left_on' => '2024-06-04']);
        $open = PossessionLocationStay::factory()->create(['possession_id' => $statue->id, 'arrived_on' => '2024-06-04']);
        $statue->update(['current_location_id' => $open->location_id]);

        new DestroyPossessionLocationStay(user: $user, stay: $open)->execute();

        $this->assertModelMissing($open);
        $this->assertModelExists($past);
        $this->assertNull($statue->refresh()->current_location_id);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionLocationStayDeletion
                && $job->parameters === ['name' => 'Ceramic cat'],
        );
    }

    #[Test]
    public function deleting_a_past_stay_keeps_the_current_location(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $statue = Possession::factory()->create(['vault_id' => $vault->id]);
        $past = PossessionLocationStay::factory()->create(['possession_id' => $statue->id, 'left_on' => '2024-06-04']);
        $open = PossessionLocationStay::factory()->create(['possession_id' => $statue->id, 'arrived_on' => '2024-06-04']);
        $statue->update(['current_location_id' => $open->location_id]);

        new DestroyPossessionLocationStay(user: $user, stay: $past)->execute();

        $this->assertModelMissing($past);
        $this->assertEquals($open->location_id, $statue->refresh()->current_location_id);
    }

    #[Test]
    public function it_refuses_a_stay_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new DestroyPossessionLocationStay(
            user: User::factory()->create(),
            stay: PossessionLocationStay::factory()->create(),
        )->execute();
    }
}
