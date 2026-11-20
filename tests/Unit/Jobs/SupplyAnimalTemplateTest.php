<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Actions\CreateDefaultValueLists;
use App\Enums\DefaultTemplateEnum;
use App\Enums\ValueListEnum;
use App\Jobs\CreateDefaultTemplate;
use App\Jobs\SupplyAnimalTemplate;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Section;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupplyAnimalTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_the_animal_template_beside_basic_animal_and_changes_nothing_the_account_had(): void
    {
        Queue::fake();

        $user = $this->accountFromBeforeTheAnimalTemplate();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        new CreateDefaultTemplate(user: $user, supplied: DefaultTemplateEnum::BasicAnimal)->handle();
        $default = VaultDefaultTemplate::query()->where('vault_id', $vault->id)->sole();
        $basicAnimal = Template::query()->where('slug', DefaultTemplateEnum::BasicAnimal->value)->sole();
        $sprinkles = Entity::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'entity_type_id' => $basicAnimal->structure->entity_type_id,
            'structure_version_id' => $basicAnimal->structure->published_version_id,
            'name' => 'Sprinkles',
        ]);

        new SupplyAnimalTemplate(user: $user)->handle();

        $this->assertEqualsCanonicalizing(
            ['basic-animal', 'animal'],
            Template::query()->where('account_id', $user->account_id)->pluck('slug')->all(),
        );
        $this->assertSame(1, EntityType::query()->where('account_id', $user->account_id)->count());
        $this->assertDatabaseHas('vault_default_templates', [
            'id' => $default->id,
            'structure_version_id' => $basicAnimal->structure->published_version_id,
        ]);
        $this->assertSame(1, VaultDefaultTemplate::query()->where('vault_id', $vault->id)->count());
        $this->assertDatabaseHas('entities', [
            'id' => $sprinkles->id,
            'structure_version_id' => $basicAnimal->structure->published_version_id,
        ]);
        $this->assertDatabaseHas('value_lists', ['account_id' => $user->account_id, 'key' => ValueListEnum::NeuterStatuses->value]);
    }

    #[Test]
    public function it_adds_the_animal_template_to_an_account_without_basic_animal(): void
    {
        Queue::fake();

        $user = $this->accountFromBeforeTheAnimalTemplate();

        new SupplyAnimalTemplate(user: $user)->handle();

        $this->assertDatabaseHas('templates', ['account_id' => $user->account_id, 'slug' => DefaultTemplateEnum::Animal->value]);
        $this->assertDatabaseHas('entity_types', ['account_id' => $user->account_id, 'slug' => 'animals']);
    }

    #[Test]
    public function it_adds_nothing_the_second_time(): void
    {
        Queue::fake();

        $user = $this->accountFromBeforeTheAnimalTemplate();

        new SupplyAnimalTemplate(user: $user)->handle();

        $sections = Section::query()->where('account_id', $user->account_id)->count();

        new SupplyAnimalTemplate(user: $user)->handle();

        $this->assertSame(1, Template::query()->where('account_id', $user->account_id)->count());
        $this->assertSame($sections, Section::query()->where('account_id', $user->account_id)->count());
    }

    #[Test]
    public function it_does_not_bring_back_the_animal_template_somebody_deleted(): void
    {
        Queue::fake();

        $user = $this->accountFromBeforeTheAnimalTemplate();

        new SupplyAnimalTemplate(user: $user)->handle();

        Template::query()->where('slug', DefaultTemplateEnum::Animal->value)->sole()->delete();

        new SupplyAnimalTemplate(user: $user)->handle();

        $this->assertSame(0, Template::query()->where('slug', DefaultTemplateEnum::Animal->value)->count());
    }

    #[Test]
    public function it_skips_an_account_whose_own_section_holds_one_of_the_slugs(): void
    {
        Queue::fake();
        Log::spy();

        $user = $this->accountFromBeforeTheAnimalTemplate();
        Section::factory()->create(['account_id' => $user->account_id, 'slug' => 'animal-health', 'is_system' => false]);

        new SupplyAnimalTemplate(user: $user)->handle();

        $this->assertSame(0, Template::query()->where('account_id', $user->account_id)->count());
        Log::shouldHaveReceived('warning')->once();
    }

    #[Test]
    public function it_skips_an_account_that_deleted_its_animal_type(): void
    {
        Queue::fake();
        Log::spy();

        $user = $this->accountFromBeforeTheAnimalTemplate();
        new CreateDefaultTemplate(user: $user, supplied: DefaultTemplateEnum::BasicAnimal)->handle();
        EntityType::query()->where('account_id', $user->account_id)->sole()->delete();

        new SupplyAnimalTemplate(user: $user)->handle();

        $this->assertSame(0, Template::query()->where('slug', DefaultTemplateEnum::Animal->value)->count());
        $this->assertSame(0, EntityType::query()->where('account_id', $user->account_id)->count());
        Log::shouldHaveReceived('warning')->once();
    }

    private function accountFromBeforeTheAnimalTemplate(): User
    {
        $user = User::factory()->create();

        new CreateDefaultValueLists(user: $user, lists: [ValueListEnum::AnimalSexes])->execute();

        return $user;
    }
}
