<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\PossessionTypes;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTypeImportCheckControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_that_a_good_file_can_be_imported_and_creates_nothing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.possessionTypeImportCheck.create'), [
            'file' => json_encode([
                'format' => 'possession_type',
                'format_version' => 1,
                'possession_type' => ['name' => 'Office equipment'],
            ]),
        ])
            ->assertStatus(200)
            ->assertSee('Office equipment');

        $this->assertDatabaseCount('possession_types', 0);
    }

    #[Test]
    public function it_lists_the_problems_of_a_bad_file(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.possessionTypeImportCheck.create'), [
            'file' => json_encode([
                'format' => 'possession_type',
                'format_version' => 1,
                'possession_type' => [
                    'name' => 'Office equipment',
                    'groups' => [['fields' => []]],
                ],
            ]),
        ])
            ->assertStatus(200);
    }

    #[Test]
    public function it_refuses_an_empty_file(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.possessionTypeImportCheck.create'), ['file' => ''])
            ->assertSessionHasErrors('file');
    }
}
