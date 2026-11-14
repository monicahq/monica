<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\Administrator;
use App\Models\PreviewListEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PreviewListOldestAdmissionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_admits_the_oldest_entries_and_says_how_many(): void
    {
        Notification::fake();

        PreviewListEntry::factory()->count(3)->create();

        $response = $this->actingAs($this->administrator())->post(route('admin.previewList.oldestAdmissions.create'), ['count' => 2]);

        $response->assertRedirect(route('admin.previewList.index'));
        $response->assertSessionHas('status', '2 people were admitted.');
    }

    #[Test]
    public function it_says_so_when_nobody_was_waiting(): void
    {
        $response = $this->actingAs($this->administrator())->post(route('admin.previewList.oldestAdmissions.create'), ['count' => 5]);

        $response->assertSessionHas('status', 'Nobody was admitted.');
    }

    /** @return array<string, array{mixed}> */
    public static function invalidCounts(): array
    {
        return [
            'nothing' => [null],
            'zero' => [0],
            'more than a hundred' => [101],
            'not a number' => ['many'],
        ];
    }

    #[Test]
    #[DataProvider('invalidCounts')]
    public function it_refuses_a_count_outside_one_to_a_hundred(mixed $count): void
    {
        $this->actingAs($this->administrator())
            ->post(route('admin.previewList.oldestAdmissions.create'), ['count' => $count])
            ->assertSessionHasErrors('count');
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        Administrator::factory()->create(['user_id' => $user->id]);

        return $user;
    }
}
