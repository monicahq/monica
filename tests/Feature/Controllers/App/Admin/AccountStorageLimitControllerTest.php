<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Actions\CreateFile;
use App\Exceptions\NotEnoughStorageLeft;
use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountStorageLimitControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_is_closed_to_somebody_who_does_not_run_the_instance(): void
    {
        $jim = User::factory()->create();
        $todd = User::factory()->create();
        $untouched = $todd->account->storage_limit_in_bytes;

        $this->actingAs($jim)
            ->put(route('admin.accounts.storageLimit.update', ['account' => $todd->account_id]), [
                'storage_limit_in_bytes' => 1024,
            ])
            ->assertNotFound();

        $this->assertEquals($untouched, $todd->account->refresh()->storage_limit_in_bytes);
    }

    #[Test]
    public function it_sets_a_limit(): void
    {
        $todd = User::factory()->create();

        $response = $this->actingAs($this->administrator())
            ->put(route('admin.accounts.storageLimit.update', ['account' => $todd->account_id]), [
                'storage_limit_in_bytes' => 2048,
            ]);

        $response->assertRedirect(route('admin.accounts.show', ['account' => $todd->account_id]));
        $response->assertSessionHas('status', 'The storage limit is saved.');

        $this->assertEquals(2048, $todd->account->refresh()->storage_limit_in_bytes);
    }

    #[Test]
    public function it_sets_a_limit_arriving_as_text_the_way_a_form_sends_it(): void
    {
        $todd = User::factory()->create();

        $response = $this->actingAs($this->administrator())
            ->put(route('admin.accounts.storageLimit.update', ['account' => $todd->account_id]), [
                'storage_limit_in_bytes' => '1048576',
            ]);

        $response->assertRedirect(route('admin.accounts.show', ['account' => $todd->account_id]));

        $this->assertSame(1048576, $todd->account->refresh()->storage_limit_in_bytes);
    }

    #[Test]
    public function an_empty_limit_means_no_limit(): void
    {
        $todd = User::factory()->create();
        $todd->account->update(['storage_limit_in_bytes' => 2048]);

        $this->actingAs($this->administrator())
            ->put(route('admin.accounts.storageLimit.update', ['account' => $todd->account_id]), [
                'storage_limit_in_bytes' => '',
            ]);

        $this->assertNull($todd->account->refresh()->storage_limit_in_bytes);
    }

    #[Test]
    public function it_refuses_a_limit_that_is_not_a_number_of_bytes(): void
    {
        $todd = User::factory()->create();

        $response = $this->actingAs($this->administrator())
            ->put(route('admin.accounts.storageLimit.update', ['account' => $todd->account_id]), [
                'storage_limit_in_bytes' => -5,
            ]);

        $response->assertSessionHasErrors('storage_limit_in_bytes');
    }

    #[Test]
    public function a_limit_under_what_is_held_deletes_nothing_and_stops_the_next_upload(): void
    {
        Storage::fake();

        $todd = User::factory()->create();
        $todd->account->update(['storage_limit_in_bytes' => 1024 * 1024]);

        $file = new CreateFile(
            user: $todd,
            upload: UploadedFile::fake()->create('beets.pdf', 8),
        )->execute();

        $this->actingAs($this->administrator())
            ->put(route('admin.accounts.storageLimit.update', ['account' => $todd->account_id]), [
                'storage_limit_in_bytes' => 1,
            ]);

        $this->assertDatabaseHas('files', ['id' => $file->id]);

        $this->expectException(NotEnoughStorageLeft::class);

        new CreateFile(
            user: $todd->refresh(),
            upload: UploadedFile::fake()->create('more-beets.pdf', 8),
        )->execute();
    }

    private function administrator(): User
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        Administrator::factory()->create(['user_id' => $michael->id]);

        return $michael;
    }
}
