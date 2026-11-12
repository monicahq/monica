<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateDefaultPossessionTypes;
use App\Actions\ExportPossessionType;
use App\Enums\PossessionFieldKindEnum;
use App\Enums\PossessionTypeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\PossessionField;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateDefaultPossessionTypesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_account_the_twenty_five_types(): void
    {
        Queue::fake();
        $user = $this->michael();

        $possessionTypes = new CreateDefaultPossessionTypes(user: $user)->execute();

        $this->assertCount(25, $possessionTypes);
        $this->assertEquals(25, PossessionType::query()->where('account_id', $user->account_id)->count());
        $this->assertDatabaseHas('possession_types', [
            'account_id' => $user->account_id,
            'colour' => '#a8447a',
            'icon' => 'food',
        ]);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionTypesSeeded);
        Queue::assertPushed(LogUserAction::class, 1);
    }

    #[Test]
    public function it_offers_every_select_field_at_least_one_choice(): void
    {
        $user = $this->michael();

        new CreateDefaultPossessionTypes(user: $user)->execute();

        $empty = PossessionField::query()
            ->where('account_id', $user->account_id)
            ->where('kind', PossessionFieldKindEnum::Select)
            ->get()
            ->filter(fn (PossessionField $field): bool => $field->options === []);

        $this->assertCount(0, $empty);
    }

    #[Test]
    public function it_gives_the_other_type_no_group_and_no_field(): void
    {
        $user = $this->michael();

        new CreateDefaultPossessionTypes(user: $user)->execute();

        $other = $this->possessionType($user, 'Other');

        $this->assertFalse($other->groups()->exists());
        $this->assertFalse($other->fields()->exists());
    }

    #[Test]
    public function it_writes_the_names_in_the_language_of_the_user(): void
    {
        $user = $this->michael(locale: 'fr_FR');

        new CreateDefaultPossessionTypes(user: $user)->execute();

        $books = $this->possessionType($user, 'Livres');
        $region = $this->field($user, 'Jeux vidéo', 'Région');

        $this->assertTrue($books->groups()->exists());
        $this->assertEquals(['NTSC', 'PAL'], $region->options);
    }

    #[Test]
    public function it_keeps_a_code_as_it_is_where_the_word_is_translated_elsewhere(): void
    {
        $user = $this->michael(locale: 'de_DE');

        new CreateDefaultPossessionTypes(user: $user)->execute();

        $publisher = PossessionField::query()
            ->where('account_id', $user->account_id)
            ->where('kind', PossessionFieldKindEnum::Select)
            ->get()
            ->first(fn (PossessionField $field): bool => in_array('Marvel', $field->options ?? [], true));

        $this->assertEquals(['Marvel', 'DC', 'Image', 'Dark Horse'], array_slice($publisher->options, 0, 4));
        $this->assertNotEquals('Independent', $publisher->options[4]);
    }

    #[Test]
    public function every_seeded_type_exports_exactly_as_the_catalogue_describes_it(): void
    {
        $user = $this->michael();

        new CreateDefaultPossessionTypes(user: $user)->execute();

        foreach (PossessionTypeEnum::cases() as $case) {
            $export = new ExportPossessionType(
                user: $user,
                possessionType: $this->possessionType($user, $case->label()),
            )->execute();

            $blocks = $case->blocks();
            $standalone = $blocks !== [] && $blocks[0]['name'] === null ? array_shift($blocks)['fields'] : [];

            $this->assertEquals($case->colour()->value, $export['possession_type']['colour'], $case->label());
            $this->assertEquals($this->exported($standalone), $export['possession_type']['fields'], $case->label());
            $this->assertEquals(
                array_map(fn (array $block): array => ['name' => $block['name'], 'fields' => $this->exported($block['fields'])], $blocks),
                $export['possession_type']['groups'],
                $case->label(),
            );
        }
    }

    #[Test]
    public function it_writes_nothing_when_the_account_holds_a_type_of_its_own(): void
    {
        Queue::fake();
        $user = $this->michael();
        PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Dundies']);

        $possessionTypes = new CreateDefaultPossessionTypes(user: $user)->execute();

        $this->assertEquals([], $possessionTypes);
        $this->assertEquals(1, PossessionType::query()->where('account_id', $user->account_id)->count());
        Queue::assertNotPushed(LogUserAction::class);
    }

    #[Test]
    public function it_leaves_twenty_five_types_when_it_runs_twice(): void
    {
        $user = $this->michael();

        new CreateDefaultPossessionTypes(user: $user)->execute();
        new CreateDefaultPossessionTypes(user: $user)->execute();

        $this->assertEquals(25, PossessionType::query()->where('account_id', $user->account_id)->count());
    }

    #[Test]
    public function it_does_not_bring_back_a_seeded_type_that_was_deleted(): void
    {
        $user = $this->michael();

        new CreateDefaultPossessionTypes(user: $user)->execute();
        $this->possessionType($user, 'Stamps')->delete();
        new CreateDefaultPossessionTypes(user: $user)->execute();

        $this->assertEquals(24, PossessionType::query()->where('account_id', $user->account_id)->count());
        $this->assertFalse(PossessionType::query()->where('account_id', $user->account_id)->where('name', 'Stamps')->exists());
    }

    private function michael(string $locale = 'en'): User
    {
        $account = Account::factory()->create();

        return $this->assignUserToAccount(user: User::factory()->make(['locale' => $locale]), account: $account);
    }

    private function possessionType(User $user, string $name): PossessionType
    {
        return PossessionType::query()
            ->where('account_id', $user->account_id)
            ->where('name', $name)
            ->firstOrFail();
    }

    private function field(User $user, string $type, string $name): PossessionField
    {
        return $this->possessionType($user, $type)->fields()->where('name', $name)->firstOrFail();
    }

    /**
     * @param  list<array{name: string, kind: PossessionFieldKindEnum, options: list<string>}>  $fields
     * @return list<array<string, mixed>>
     */
    private function exported(array $fields): array
    {
        return array_map(fn (array $field): array => $field['kind'] === PossessionFieldKindEnum::Select
            ? ['name' => $field['name'], 'kind' => $field['kind']->value, 'options' => $field['options']]
            : ['name' => $field['name'], 'kind' => $field['kind']->value], $fields);
    }
}
