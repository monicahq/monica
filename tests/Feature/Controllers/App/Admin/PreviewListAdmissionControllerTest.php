<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\Administrator;
use App\Models\PreviewListEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PreviewListAdmissionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_admits_an_entry(): void
    {
        Notification::fake();

        $entry = PreviewListEntry::factory()->create(['email' => 'meredith.palmer@dundermifflin.com']);

        $response = $this->actingAs($this->administrator())->post(route('admin.previewList.admission.create', ['previewListEntry' => $entry->id]));

        $response->assertRedirect(route('admin.previewList.index'));
        $response->assertSessionHas('status', 'meredith.palmer@dundermifflin.com is admitted.');
        $this->assertNotNull($entry->refresh()->admitted_at);
    }

    #[Test]
    public function it_says_why_an_address_holding_an_account_is_refused(): void
    {
        User::factory()->create(['email' => 'meredith.palmer@dundermifflin.com']);
        $entry = PreviewListEntry::factory()->create(['email' => 'meredith.palmer@dundermifflin.com']);

        $response = $this->actingAs($this->administrator())
            ->from(route('admin.previewList.index'))
            ->post(route('admin.previewList.admission.create', ['previewListEntry' => $entry->id]));

        $response->assertRedirect(route('admin.previewList.index'));
        $response->assertSessionHasErrors(['previewList' => 'meredith.palmer@dundermifflin.com already holds an account on this instance.']);
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        Administrator::factory()->create(['user_id' => $user->id]);

        return $user;
    }
}
