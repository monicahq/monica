<?php

declare(strict_types=1);

namespace Tests\Unit\Seeders;

use App\Models\Possession;
use App\Models\User;
use Database\Seeders\ExampleAccountSeeder;
use Database\Seeders\ExamplePossessions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExampleAccountSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_fills_the_example_account_with_its_possessions(): void
    {
        new ExampleAccountSeeder()->run();

        $user = User::query()->where('email', ExampleAccountSeeder::EMAIL)->firstOrFail();

        $this->assertCount(count(new ExamplePossessions()->all()), Possession::query()->where('account_id', $user->account_id)->get());

        $ring = Possession::query()->where('name', 'Engagement ring')->firstOrFail();

        $this->assertTrue($ring->is_featured);
        $this->assertEquals('Bedroom', $ring->location?->name);
        $this->assertEquals('Bedroom', $ring->openLocationStay?->location->name);
        $this->assertDatabaseHas('possession_field_values', [
            'possession_id' => $ring->id,
            'value' => 'Ring',
        ]);
        $this->assertDatabaseHas('possession_transactions', [
            'possession_id' => $ring->id,
            'counterparty' => 'Tiffany',
        ]);
        $this->assertDatabaseHas('possession_insurance_records', [
            'possession_id' => $ring->id,
            'insurer' => 'Chubb',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('possession_maintenance_records', [
            'possession_id' => $ring->id,
            'title' => 'Cleaning and prong check',
        ]);
        $this->assertDatabaseHas('possession_valuations', [
            'possession_id' => $ring->id,
            'valuer' => 'Diamond District Appraisers',
            'amount' => '6200.00',
        ]);
        $this->assertDatabaseHas('possession_provenance_events', [
            'possession_id' => $ring->id,
            'type' => 'acquisition',
            'to' => 'Chandler Bing',
            'possession_transaction_id' => $ring->transactions()->sole()->id,
        ]);
        $this->assertEquals('Rachel Green', $ring->loans()->sole()->contact?->name);
    }
}
