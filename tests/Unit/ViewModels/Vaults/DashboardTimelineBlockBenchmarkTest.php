<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventType;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\DashboardTimelineBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('benchmark')]
class DashboardTimelineBlockBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    private const int LIFE_EVENTS = 20000;

    private const int PEOPLE = 2000;

    private const int YEARS = 30;

    private const int MILLISECONDS = 100;

    /** @return array<string, array{0: bool, 1: bool, 2: string|null}> */
    public static function filters(): array
    {
        return [
            'the default slice' => [false, false, null],
            'a year' => [false, false, '2010'],
            'a type' => [true, false, '2010'],
            'a person' => [false, true, '2010'],
            'a type and a person' => [true, true, null],
        ];
    }

    #[Test]
    #[DataProvider('filters')]
    public function it_draws_the_timeline_of_a_large_vault_in_time(bool $byType, bool $byContact, ?string $slice): void
    {
        $this->travelTo('2026-11-22 10:00:00');

        $user = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        [$types, $people] = $this->fill($vault);

        $block = new DashboardTimelineBlock(
            user: $user,
            vault: $vault,
            slice: $slice,
            contact: $byContact ? $people[7]->uuid : null,
            type: $byType ? $types[3]->uuid : null,
        );

        $started = hrtime(true);

        $block->total();
        $block->index();
        $block->title();
        $block->countLabel();
        $block->typeOptions();
        $block->contactChoice();
        $block->isEmptySlice();
        $block->months();
        $block->nextPageUrl();
        $block->newer();
        $block->older();

        $milliseconds = (hrtime(true) - $started) / 1_000_000;

        $this->assertLessThan(self::MILLISECONDS, $milliseconds, "Drawn in {$milliseconds} ms.");
    }

    /** @return array{0: list<LifeEventType>, 1: list<Contact>} */
    private function fill(Vault $vault): array
    {
        $template = LifeEvent::factory()->create(['vault_id' => $vault->id]);
        $types = [LifeEventType::query()->findOrFail($template->life_event_type_id)];

        for ($index = 1; $index < 8; $index++) {
            $types[] = LifeEventType::factory()->create([
                'account_id' => $vault->account_id,
                'life_event_category_id' => $types[0]->life_event_category_id,
            ]);
        }

        $contact = collect(Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id])->getAttributes())->except(['id'])->all();
        $contacts = [];

        for ($index = 1; $index < self::PEOPLE; $index++) {
            $contacts[] = [...$contact, 'uuid' => (string) Str::uuid(), 'slug' => "person-{$index}", 'sort_name' => "Person {$index}", 'search_key' => "person {$index}"];
        }

        foreach (array_chunk($contacts, 200) as $chunk) {
            DB::table('contacts')->insert($chunk);
        }

        $contactIds = DB::table('contacts')->where('vault_id', $vault->id)->pluck('id')->all();
        $row = collect($template->getAttributes())->except(['id', 'uuid'])->all();
        $lifeEvents = [];

        for ($index = 0; $index < self::LIFE_EVENTS; $index++) {
            $lifeEvents[] = [
                ...$row,
                'uuid' => (string) Str::uuid(),
                'life_event_type_id' => $types[$index % count($types)]->id,
                'happened_on' => sprintf('%d-%02d-%02d', 1997 + $index % self::YEARS, 1 + $index % 12, 1 + $index % 28),
            ];
        }

        foreach (array_chunk($lifeEvents, 500) as $chunk) {
            DB::table('life_events')->insert($chunk);
        }

        $participants = [];

        foreach (DB::table('life_events')->where('vault_id', $vault->id)->pluck('id') as $position => $lifeEventId) {
            foreach ([0, 1] as $offset) {
                $participants[] = [
                    'account_id' => $vault->account_id,
                    'life_event_id' => $lifeEventId,
                    'participant_type' => new Contact()->getMorphClass(),
                    'participant_id' => $contactIds[($position * 2 + $offset) % count($contactIds)],
                    'position' => $offset,
                ];
            }
        }

        foreach (array_chunk($participants, 1000) as $chunk) {
            DB::table('life_event_participants')->insert($chunk);
        }

        return [$types, Contact::query()->whereKey(array_slice($contactIds, 0, 10))->get()->all()];
    }
}
