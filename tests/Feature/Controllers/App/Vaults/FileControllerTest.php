<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\File;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FileControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hands_back_the_bytes_under_the_name_somebody_uploaded(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $file = $this->fileIn($vault, $user, 'lease.pdf');

        $response = $this->actingAs($user)->get(route('files.show', ['vault' => $vault, 'file' => $file]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('max-age=31536000', (string) $response->headers->get('cache-control'));
        $this->assertStringContainsString('lease.pdf', (string) $response->headers->get('content-disposition'));
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function dispositions(): array
    {
        return [
            'an html page' => ['dundie-awards.html', 'text/html', 'attachment'],
            'an svg drawing' => ['dunder-mifflin-logo.svg', 'image/svg+xml', 'attachment'],
            'a png picture' => ['michael-scott.png', 'image/png', 'inline'],
        ];
    }

    #[Test]
    #[DataProvider('dispositions')]
    public function it_shows_only_a_raster_image_in_place_and_forbids_sniffing(string $name, string $mimeType, string $disposition): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $file = $this->fileIn($vault, $user, $name, $mimeType);

        $response = $this->actingAs($user)->get(route('files.show', ['vault' => $vault, 'file' => $file]));

        $response->assertStatus(200);
        $response->assertHeader('x-content-type-options', 'nosniff');
        $this->assertStringStartsWith("{$disposition}; filename={$name}", (string) $response->headers->get('content-disposition'));
    }

    #[Test]
    public function it_does_not_find_a_file_whose_bytes_have_gone(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $file = File::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'disk' => 'local',
            'path' => 'accounts/nothing/here',
        ]);

        $this->actingAs($user)->get(route('files.show', ['vault' => $vault, 'file' => $file]))
            ->assertNotFound();
    }

    private function fileIn(Vault $vault, User $user, string $name, string $mimeType = 'application/pdf'): File
    {
        $path = File::directoryFor($vault->account_id, $vault->id).'/'.fake()->uuid();

        Storage::disk('local')->put($path, 'the bytes');

        return File::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'uploaded_by_user_id' => $user->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $name,
            'mime_type' => $mimeType,
        ]);
    }
}
