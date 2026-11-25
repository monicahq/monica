<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Group;
use App\Models\GroupFile;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupFileControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_files_tab(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();

        $this->actingAs($user)->get(route('groups.files.index', ['vault' => $vault, 'group' => $group]))
            ->assertOk()
            ->assertSee('Keep it with the group');
    }

    #[Test]
    public function it_keeps_a_file_with_the_group(): void
    {
        Queue::fake();
        Storage::fake('local');
        [$user, $vault, $group] = $this->finerThingsClub();

        $this->actingAs($user)
            ->from(route('groups.files.index', ['vault' => $vault, 'group' => $group]))
            ->post(route('groups.files.create', ['vault' => $vault, 'group' => $group]), [
                'file' => UploadedFile::fake()->create('reading-list.pdf', 4, 'application/pdf'),
            ])
            ->assertRedirect(route('groups.files.index', ['vault' => $vault, 'group' => $group]))
            ->assertSessionHas('status', 'The file is kept with the group.');

        $this->assertDatabaseCount('group_files', 1);
    }

    #[Test]
    public function it_refuses_a_file_the_account_has_no_room_for_on_the_form(): void
    {
        Storage::fake('local');
        [$user, $vault, $group] = $this->finerThingsClub();
        $user->account->update(['storage_limit_in_bytes' => 1024, 'storage_used_in_bytes' => 512]);

        $this->actingAs($user)->post(route('groups.files.create', ['vault' => $vault, 'group' => $group]), [
            'file' => UploadedFile::fake()->create('scan.pdf', 4),
        ])->assertSessionHasErrors('file');
    }

    #[Test]
    public function it_refuses_a_request_without_a_file(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();

        $this->actingAs($user)->post(route('groups.files.create', ['vault' => $vault, 'group' => $group]), [])
            ->assertSessionHasErrors('file');
    }

    #[Test]
    public function it_stops_keeping_a_file_with_the_group(): void
    {
        Queue::fake();
        [$user, $vault, $group] = $this->finerThingsClub();
        $attachment = GroupFile::factory()->create(['group_id' => $group->id]);

        $this->actingAs($user)->delete(route('groups.files.destroy', ['vault' => $vault, 'group' => $group, 'groupFile' => $attachment]))
            ->assertRedirect(route('groups.files.index', ['vault' => $vault, 'group' => $group]));

        $this->assertSoftDeleted($attachment);
    }

    #[Test]
    public function it_does_not_find_a_file_through_another_group(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();
        $attachment = GroupFile::factory()->create(['group_id' => Group::factory()->create(['vault_id' => $vault->id])->id]);

        $this->actingAs($user)->delete(route('groups.files.destroy', ['vault' => $vault, 'group' => $group, 'groupFile' => $attachment]))
            ->assertNotFound();
    }

    #[Test]
    public function it_answers_not_found_when_files_are_not_offered(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();
        $user->account->features()->deactivate('file-upload');
        Feature::flushCache();

        $this->actingAs($user)->get(route('groups.files.index', ['vault' => $vault, 'group' => $group]))
            ->assertNotFound();
    }

    /** @return array{0: User, 1: Vault, 2: Group} */
    private function finerThingsClub(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, $vault, Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Finer things club'])];
    }
}
