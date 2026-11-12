<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\PossessionField;
use App\Models\PossessionType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTypeFieldControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_answers_the_fields_of_a_type(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $vehicle = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Vehicle']);
        $registration = PossessionField::factory()->create(['possession_type_id' => $vehicle->id, 'name' => 'Registration']);

        $this->actingAs($user)->get(route('possessions.typeFields.index', ['vault' => $vault, 'possessionType' => $vehicle]))
            ->assertStatus(200)
            ->assertSee('id="possession-type-fields"', escape: false)
            ->assertSee('Registration')
            ->assertSee('name="values['.$registration->uuid.']"', escape: false);
    }

    #[Test]
    public function it_does_not_find_a_type_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('possessions.typeFields.index', ['vault' => $vault, 'possessionType' => PossessionType::factory()->create()]))
            ->assertNotFound();
    }
}
