<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\PossessionTypes;

use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTypeImportControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_import_screen(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('settings.possessionTypeImport.new'))
            ->assertStatus(200);
    }

    #[Test]
    public function it_imports_a_file_and_opens_the_new_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('settings.possessionTypeImport.create'), [
            'file' => json_encode([
                'format' => 'possession_type',
                'format_version' => 1,
                'possession_type' => [
                    'name' => 'Office equipment',
                    'fields' => [['name' => 'Serial number', 'kind' => 'text']],
                ],
            ]),
        ]);

        $possessionType = PossessionType::query()->where('account_id', $user->account_id)->sole();

        $response->assertRedirect(route('settings.possessionTypes.edit', $possessionType))
            ->assertSessionHas('status');

        $this->assertSame('Office equipment', $possessionType->name);
        $this->assertSame(1, $possessionType->fields()->count());
    }

    #[Test]
    public function it_sends_an_unreadable_file_back_with_errors_on_the_file(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.possessionTypeImport.create'), ['file' => '{"format": "other"}'])
            ->assertRedirect(route('settings.possessionTypeImport.new'))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('possession_types', 0);
    }

    #[Test]
    public function it_refuses_an_empty_file(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.possessionTypeImport.create'), ['file' => ''])
            ->assertSessionHasErrors('file');
    }
}
