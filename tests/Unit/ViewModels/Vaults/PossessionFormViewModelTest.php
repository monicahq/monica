<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionFieldKindEnum;
use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionField;
use App\Models\PossessionFieldValue;
use App\Models\PossessionTag;
use App\Models\PossessionType;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\PossessionFormViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionFormViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_types_of_the_account_by_name_with_their_field_count(): void
    {
        [$user, $vault] = $this->scranton();
        $vehicle = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Vehicle']);
        PossessionField::factory()->count(2)->create(['possession_type_id' => $vehicle->id]);
        PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Books']);
        PossessionType::factory()->create(['name' => 'Paper']);

        $viewModel = new PossessionFormViewModel(user: $user, vault: $vault);

        $this->assertSame(['Books', 'Vehicle'], array_column($viewModel->types(), 'name'));
        $this->assertSame('2 fields', $viewModel->types()[1]['fieldCount']);
        $this->assertSame('0 fields', $viewModel->types()[0]['fieldCount']);
    }

    #[Test]
    public function it_chooses_the_type_picked_on_all_possessions_unless_a_refused_save_chose_another(): void
    {
        [$user, $vault] = $this->scranton();
        $vehicle = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Vehicle']);
        $books = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Books']);

        $viewModel = new PossessionFormViewModel(user: $user, vault: $vault, chosenType: $vehicle);

        $this->assertSame($vehicle->uuid, $viewModel->chosenType());
        $this->assertSame('Vehicle', $viewModel->fieldsBlock()?->typeName());

        $session = $this->app['session.store'];
        $session->flashInput(['type' => $books->uuid]);
        request()->setLaravelSession($session);

        $this->assertSame($books->uuid, new PossessionFormViewModel(user: $user, vault: $vault, chosenType: $vehicle)->chosenType());
    }

    #[Test]
    public function it_offers_the_types_most_recently_used_in_the_vault_at_most_four_once_each(): void
    {
        [$user, $vault] = $this->scranton();
        $types = collect(['Books', 'Vehicle', 'Wine', 'Tools', 'Watch'])
            ->mapWithKeys(fn (string $name): array => [$name => PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => $name])]);

        foreach (['Watch', 'Books', 'Vehicle', 'Wine', 'Vehicle', 'Tools'] as $name) {
            Possession::factory()->create(['vault_id' => $vault->id, 'possession_type_id' => $types[$name]->id]);
        }

        Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id, 'possession_type_id' => $types['Watch']->id]);

        $viewModel = new PossessionFormViewModel(user: $user, vault: $vault);

        $this->assertSame(['Tools', 'Vehicle', 'Wine', 'Books'], array_column($viewModel->recentTypes(), 'name'));
    }

    #[Test]
    public function it_gives_each_location_its_path_and_the_nearest_address(): void
    {
        [$user, $vault] = $this->scranton();
        $home = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Home', 'address' => '1725 Slough Avenue']);
        Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Living room', 'parent_id' => $home->id]);
        Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Storage unit']);

        $locations = collect(new PossessionFormViewModel(user: $user, vault: $vault)->locations())->keyBy('path');

        $this->assertSame('1725 Slough Avenue', $locations['Home']['address']);
        $this->assertNull($locations['Storage unit']['address']);
    }

    #[Test]
    public function it_defaults_the_currency_to_the_one_of_the_language(): void
    {
        [$user, $vault] = $this->scranton();
        $user->update(['locale' => 'fr_FR']);

        $this->assertSame(CurrencyEnum::Euro, new PossessionFormViewModel(user: $user, vault: $vault)->currency());

        $user->update(['locale' => 'en']);

        $this->assertSame(CurrencyEnum::PoundSterling, new PossessionFormViewModel(user: $user, vault: $vault)->currency());
    }

    #[Test]
    public function it_writes_back_what_was_typed_before_a_refusal(): void
    {
        [$user, $vault] = $this->scranton();
        $vehicle = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Vehicle']);
        PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'insured']);
        $session = $this->app['session.store'];
        $session->flashInput([
            'name' => 'Porcelain cat',
            'type' => $vehicle->uuid,
            'declared_value_currency' => 'chf',
            'tags' => ['insured', 'fragile'],
        ]);
        request()->setLaravelSession($session);

        $viewModel = new PossessionFormViewModel(user: $user, vault: $vault);

        $this->assertSame('Porcelain cat', $viewModel->name());
        $this->assertSame($vehicle->uuid, $viewModel->chosenType());
        $this->assertSame('Vehicle', $viewModel->fieldsBlock()?->typeName());
        $this->assertSame(CurrencyEnum::SwissFranc, $viewModel->currency());
        $this->assertSame(['insured', 'fragile'], $viewModel->chosenTags());
        $this->assertSame(['insured'], $viewModel->accountTags());
    }

    #[Test]
    public function it_fills_the_form_with_the_possession_being_edited(): void
    {
        [$user, $vault] = $this->scranton();
        $warehouse = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Warehouse']);
        $sebring = Possession::factory()->create([
            'vault_id' => $vault->id,
            'name' => 'Chrysler Sebring',
            'description' => 'Bought from a lot in Scranton',
            'current_location_id' => $warehouse->id,
            'declared_value' => '4500',
            'declared_value_currency' => CurrencyEnum::PoundSterling->value,
            'is_featured' => true,
            'position' => 1,
        ]);
        $sebring->tags()->attach(PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'insured']));
        $mileage = PossessionField::factory()->create(['possession_type_id' => $sebring->possession_type_id, 'name' => 'Mileage', 'kind' => PossessionFieldKindEnum::Number]);
        PossessionFieldValue::factory()->create(['possession_id' => $sebring->id, 'possession_field_id' => $mileage->id, 'value' => '120000']);

        $viewModel = new PossessionFormViewModel(user: $user, vault: $vault, possession: $sebring);

        $this->assertTrue($viewModel->isEditing());
        $this->assertSame('Edit Chrysler Sebring', $viewModel->title());
        $this->assertSame('Chrysler Sebring', $viewModel->name());
        $this->assertSame('Bought from a lot in Scranton', $viewModel->description());
        $this->assertSame($warehouse->uuid, $viewModel->chosenLocation());
        $this->assertSame('4500.00', $viewModel->declaredValue());
        $this->assertSame(CurrencyEnum::PoundSterling, $viewModel->currency());
        $this->assertSame(['insured'], $viewModel->chosenTags());
        $this->assertTrue($viewModel->isFeatured());
        $this->assertSame($sebring->possessionType->uuid, $viewModel->chosenType());
        $this->assertSame('120000', $viewModel->fieldsBlock()?->standaloneFields()[0]['old']);
        $this->assertSame('put', $viewModel->formMethod());
        $this->assertSame(route('possessions.update', [$vault, $sebring]), $viewModel->formAction());
        $this->assertSame(route('possessions.show', [$vault, $sebring]), $viewModel->cancelUrl());
    }

    #[Test]
    public function it_prefers_what_was_typed_before_a_refused_save_over_the_possession_being_edited(): void
    {
        [$user, $vault] = $this->scranton();
        $sebring = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Chrysler Sebring', 'is_featured' => true, 'position' => 1]);
        $sebring->tags()->attach(PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'insured']));

        $session = $this->app['session.store'];
        $session->flashInput(['name' => 'Sebring convertible']);
        request()->setLaravelSession($session);

        $viewModel = new PossessionFormViewModel(user: $user, vault: $vault, possession: $sebring);

        $this->assertSame('Sebring convertible', $viewModel->name());
        $this->assertSame([], $viewModel->chosenTags());
        $this->assertFalse($viewModel->isFeatured());
    }

    #[Test]
    public function it_adds_a_possession_when_none_is_being_edited(): void
    {
        [$user, $vault] = $this->scranton();

        $viewModel = new PossessionFormViewModel(user: $user, vault: $vault);

        $this->assertFalse($viewModel->isEditing());
        $this->assertSame('Add a possession', $viewModel->title());
        $this->assertSame('post', $viewModel->formMethod());
        $this->assertSame(route('possessions.create', $vault), $viewModel->formAction());
        $this->assertSame(route('possessions.index', $vault), $viewModel->cancelUrl());
    }

    /** @return array{0: User, 1: Vault} */
    private function scranton(): array
    {
        $user = User::factory()->create();

        return [$user, Vault::factory()->create(['account_id' => $user->account_id])];
    }
}
