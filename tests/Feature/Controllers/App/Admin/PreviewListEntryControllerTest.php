<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\Administrator;
use App\Models\PreviewListEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PreviewListEntryControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_preview_list(): void
    {
        PreviewListEntry::factory()->create(['email' => 'angela.martin@dundermifflin.com']);

        $response = $this->actingAs($this->administrator())->get(route('admin.previewList.index'));

        $response->assertOk();
        $response->assertSee('Waiting list');
        $response->assertSee('angela.martin@dundermifflin.com');
    }

    #[Test]
    public function it_removes_an_entry(): void
    {
        $entry = PreviewListEntry::factory()->create(['email' => 'angela.martin@dundermifflin.com']);

        $response = $this->actingAs($this->administrator())->delete(route('admin.previewList.destroy', ['previewListEntry' => $entry->id]));

        $response->assertRedirect(route('admin.previewList.index'));
        $response->assertSessionHas('status', 'angela.martin@dundermifflin.com is off the waiting list.');
        $this->assertModelMissing($entry);
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        Administrator::factory()->create(['user_id' => $user->id]);

        return $user;
    }
}
