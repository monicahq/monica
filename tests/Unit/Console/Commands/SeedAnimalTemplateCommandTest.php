<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Actions\CreateDefaultValueLists;
use App\Enums\ValueListEnum;
use App\Jobs\CreateDefaultTemplate;
use App\Models\EntityType;
use App\Models\Section;
use App\Models\Template;
use App\Models\User;
use App\Models\ValueList;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeedAnimalTemplateCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_seeds_an_account_that_has_no_animal_template(): void
    {
        Queue::fake();

        $user = $this->accountFromBeforeAnimals();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->artisan('monica:seed-animal-template', ['--account' => $user->account_id])
            ->expectsOutputToContain('Seeded 1, already done 0, skipped 0.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('entity_types', ['account_id' => $user->account_id, 'slug' => 'animals']);
        $this->assertDatabaseHas('templates', ['account_id' => $user->account_id, 'slug' => 'basic-animal', 'is_system' => true]);
        $this->assertDatabaseHas('value_lists', ['account_id' => $user->account_id, 'key' => ValueListEnum::AnimalSexes->value]);
        $this->assertDatabaseHas('vault_default_templates', ['vault_id' => $vault->id, 'applies_to' => 'entity']);
    }

    #[Test]
    public function it_writes_nothing_on_a_second_run(): void
    {
        Queue::fake();

        $user = $this->accountFromBeforeAnimals();
        Vault::factory()->create(['account_id' => $user->account_id]);

        $this->artisan('monica:seed-animal-template')->assertExitCode(0);

        $this->artisan('monica:seed-animal-template')
            ->expectsOutputToContain('Seeded 0, already done 1, skipped 0.')
            ->assertExitCode(0);

        $this->assertSame(1, EntityType::query()->where('account_id', $user->account_id)->count());
        $this->assertSame(2, Template::query()->where('account_id', $user->account_id)->count());
        $this->assertSame(1, ValueList::query()->where('account_id', $user->account_id)->where('key', ValueListEnum::AnimalSexes->value)->count());
        $this->assertSame(1, VaultDefaultTemplate::query()->where('account_id', $user->account_id)->count());
    }

    #[Test]
    public function it_skips_an_account_whose_own_type_holds_the_slug(): void
    {
        Queue::fake();

        $user = $this->accountFromBeforeAnimals();
        $own = EntityType::factory()->create([
            'account_id' => $user->account_id,
            'slug' => 'animals',
            'name_singular' => 'Beet pest',
            'name_plural' => 'Beet pests',
        ]);

        $this->artisan('monica:seed-animal-template')
            ->expectsOutputToContain('Skipped: the slug animals is already used by one of its own.')
            ->expectsOutputToContain('Seeded 0, already done 0, skipped 1.')
            ->assertExitCode(0);

        $this->assertSame('Beet pests', $own->refresh()->name_plural);
        $this->assertSame(0, Template::query()->where('account_id', $user->account_id)->where('slug', 'basic-animal')->count());
    }

    #[Test]
    public function it_keeps_the_edits_made_to_the_supplied_template(): void
    {
        Queue::fake();

        $user = $this->accountFromBeforeAnimals();

        $this->artisan('monica:seed-animal-template')->assertExitCode(0);

        $template = Template::query()->where('account_id', $user->account_id)->where('slug', 'basic-animal')->sole();
        $template->update(['name' => 'Schrute Farms livestock']);
        Section::query()->where('account_id', $user->account_id)->where('slug', 'animal-care')->update(['name' => 'Feeding']);

        $this->artisan('monica:seed-animal-template')->assertExitCode(0);

        $this->assertSame('Schrute Farms livestock', $template->refresh()->name);
        $this->assertSame('Feeding', Section::query()->where('account_id', $user->account_id)->where('slug', 'animal-care')->value('name'));
    }

    #[Test]
    public function it_fails_on_an_unknown_account(): void
    {
        $this->artisan('monica:seed-animal-template', ['--account' => 999])
            ->expectsOutputToContain('No account has the id 999.')
            ->assertExitCode(1);
    }

    private function accountFromBeforeAnimals(): User
    {
        $user = User::factory()->create();

        new CreateDefaultValueLists(user: $user)->execute();
        ValueList::query()->where('account_id', $user->account_id)->where('key', ValueListEnum::AnimalSexes->value)->forceDelete();
        new CreateDefaultTemplate(user: $user)->handle();

        return $user;
    }
}
