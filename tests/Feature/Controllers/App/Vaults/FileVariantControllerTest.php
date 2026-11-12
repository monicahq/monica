<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\File;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FileVariantControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hands_back_the_smaller_copy_for_a_long_time(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $file = $this->pictureIn($vault, [['suffix' => '_96', 'pixels' => 96]]);

        Storage::disk('local')->put("{$file->path}_96", 'the small bytes');

        $response = $this->actingAs($user)->get(route('files.variants.show', ['vault' => $vault, 'file' => $file, 'size' => 96]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'image/webp');
        $this->assertSame('the small bytes', $response->streamedContent());
        $this->assertStringContainsString('max-age=31536000', (string) $response->headers->get('cache-control'));
    }

    #[Test]
    public function it_hands_back_the_original_when_no_smaller_copy_was_written(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $file = $this->pictureIn($vault, null);

        $response = $this->actingAs($user)->get(route('files.variants.show', ['vault' => $vault, 'file' => $file, 'size' => 96]));

        $response->assertStatus(200);
        $this->assertSame('the large bytes', $response->streamedContent());
    }

    /** @param list<array{suffix: string, pixels: int}>|null $variants */
    private function pictureIn(Vault $vault, ?array $variants): File
    {
        $path = File::directoryFor($vault->account_id, $vault->id).'/'.fake()->uuid();

        Storage::disk('local')->put($path, 'the large bytes');

        return File::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => 'dwight.webp',
            'mime_type' => 'image/webp',
            'variants' => $variants,
        ]);
    }
}
