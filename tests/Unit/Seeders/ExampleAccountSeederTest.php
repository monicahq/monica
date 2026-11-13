<?php

declare(strict_types=1);

namespace Tests\Unit\Seeders;

use App\Models\KnowledgeList;
use App\Models\KnowledgeNote;
use App\Models\Possession;
use App\Models\User;
use Database\Seeders\ExampleAccountSeeder;
use Database\Seeders\ExampleKnowledge;
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

    #[Test]
    public function it_fills_the_example_account_with_its_knowledge(): void
    {
        new ExampleAccountSeeder()->run();

        $user = User::query()->where('email', ExampleAccountSeeder::EMAIL)->firstOrFail();
        $vault = $user->account->vaults()->firstOrFail();

        $this->assertEquals(
            ['Personal', 'Travel', 'Home', 'Health and Fitness', 'Books and Movies', 'Food and Recipes', 'Technology', 'Ideas and Projects'],
            $vault->knowledgeFolders()->pluck('name')->all(),
        );
        $this->assertSame(17, KnowledgeNote::query()->where('vault_id', $vault->id)->count());
        $this->assertSame(13, KnowledgeList::query()->where('vault_id', $vault->id)->count());
        $this->assertSame(count(new ExampleKnowledge()->all()), $vault->knowledgeFolders()->count());

        $maintenance = KnowledgeNote::query()->where('title', 'Home maintenance')->firstOrFail();

        $this->assertStringContainsString('- [x] ', (string) $maintenance->body);
        $this->assertStringContainsString('- [ ] ', (string) $maintenance->body);
        $this->assertStringContainsString('```', (string) KnowledgeNote::query()->where('title', 'Useful commands')->firstOrFail()->body);
        $this->assertSame('Food and Recipes', KnowledgeNote::query()->where('title', "Grandma's lasagna")->firstOrFail()->folder->name);

        $appliances = KnowledgeList::query()->where('title', 'Appliances')->firstOrFail();
        $columns = $appliances->columns()->get();
        $refrigerator = $appliances->items()->get()->firstOrFail(fn ($item): bool => $item->valueFor($columns[0]) === 'Refrigerator');

        $this->assertEquals(['Appliance', 'Brand', 'Model', 'Bought on', 'Warranty until'], $columns->pluck('name')->all());
        $this->assertSame('Sub-Zero', $refrigerator->valueFor($columns[1]));
        $this->assertNull($appliances->items()->get()->last()?->valueFor($columns[4]));
    }
}
