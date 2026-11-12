<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Possession;
use App\Models\PossessionValuation;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionValuationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_valuations_of_a_possession(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Dundie award']);
        PossessionValuation::factory()->create(['possession_id' => $dundie->id, 'valuer' => 'Scranton Jewelers']);

        $this->actingAs($user)->get(route('possessions.valuations.index', [$vault, $dundie]))
            ->assertOk()
            ->assertSee('Scranton Jewelers')
            ->assertSee('Record a valuation')
            ->assertSee('aria-current="page"', escape: false)
            ->assertDontSee('@js(', escape: false);
    }

    #[Test]
    public function it_records_a_valuation_with_its_document(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);

        $this->actingAs($user)->post(route('possessions.valuations.create', [$vault, $dundie]), [
            'type' => 'appraisal',
            'valued_on' => '2026-03-14',
            'confidence' => 'certain',
            'amount' => '650',
            'currency' => 'usd',
            'valuer' => 'Scranton Jewelers',
            'document' => UploadedFile::fake()->create('appraisal-0212.pdf', 4, 'application/pdf'),
        ])
            ->assertRedirect(route('possessions.valuations.index', [$vault, $dundie]))
            ->assertSessionHas('status');

        $valuation = PossessionValuation::query()->sole();

        $this->assertDatabaseHas('possession_valuations', [
            'id' => $valuation->id,
            'possession_id' => $dundie->id,
            'type' => 'appraisal',
            'amount' => '650.00',
            'valuer' => 'Scranton Jewelers',
        ]);
        $this->assertSame(['appraisal-0212.pdf'], $valuation->documents->pluck('original_name')->all());
    }

    #[Test]
    public function it_refuses_a_valuation_without_a_date(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);

        $this->actingAs($user)->post(route('possessions.valuations.create', [$vault, $dundie]), [
            'type' => 'own_estimate',
            'confidence' => 'unknown',
            'amount' => '650',
            'currency' => 'usd',
        ])->assertSessionHasErrors('valued_on');

        $this->assertDatabaseCount('possession_valuations', 0);
    }

    #[Test]
    public function it_deletes_a_valuation(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        $valuation = PossessionValuation::factory()->create(['possession_id' => $dundie->id]);

        $this->actingAs($user)->delete(route('possessions.valuations.destroy', [$vault, $dundie, $valuation]))
            ->assertRedirect(route('possessions.valuations.index', [$vault, $dundie]))
            ->assertSessionHas('status');

        $this->assertModelMissing($valuation);
    }

    #[Test]
    public function it_answers_not_found_for_a_valuation_of_another_possession(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        $teapot = Possession::factory()->create(['vault_id' => $vault->id]);
        $valuation = PossessionValuation::factory()->create(['possession_id' => $teapot->id]);

        $this->actingAs($user)->delete(route('possessions.valuations.destroy', [$vault, $dundie, $valuation]))
            ->assertNotFound();

        $this->assertModelExists($valuation);
    }
}
