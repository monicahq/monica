<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Statistics\AnimalsVault;
use Tests\TestCase;

class RecordStatisticsControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_statistics_of_a_kind_of_record(): void
    {
        $fixture = AnimalsVault::build();

        $this->actingAs($fixture->user)
            ->get(route('entities.statistics.index', ['vault' => $fixture->vault, 'entityType' => $fixture->animals]))
            ->assertStatus(200)
            ->assertSee('Animals statistics')
            ->assertSee('Field completion');
    }

    #[Test]
    public function it_shows_the_statistics_of_people(): void
    {
        $fixture = AnimalsVault::build();

        $this->actingAs($fixture->user)
            ->get(route('contacts.statistics.index', ['vault' => $fixture->vault]))
            ->assertStatus(200)
            ->assertSee('People statistics');
    }

    #[Test]
    public function it_refuses_a_version_that_is_not_a_number(): void
    {
        $fixture = AnimalsVault::build();

        $this->actingAs($fixture->user)
            ->get(route('entities.statistics.index', ['vault' => $fixture->vault, 'entityType' => $fixture->animals, 'version' => 'latest']))
            ->assertSessionHasErrors('version');
    }
}
