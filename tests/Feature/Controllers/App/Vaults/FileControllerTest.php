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
        $this->assertStringContainsString('lease.pdf', (string) $response->headers->get('content-disposition'));
    }

    #[Test]
    public function it_does_not_find_a_file_kept_in_another_vault(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $file = $this->fileIn($vault, $user, 'lease.pdf');

        $this->actingAs($user)->get(route('files.show', ['vault' => $elsewhere, 'file' => $file]))
            ->assertNotFound();
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

    private function fileIn(Vault $vault, User $user, string $name): File
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
            'mime_type' => 'application/pdf',
        ]);
    }
}
