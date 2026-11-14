<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RemovePreviewListEntry;
use App\Enums\AdministratorActionEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\PreviewListEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RemovePreviewListEntryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_the_address_off_the_list_and_ends_its_link(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $entry = PreviewListEntry::factory()->admitted('roy-anderson-token')->create(['email' => 'roy.anderson@dundermifflin.com']);

        new RemovePreviewListEntry(administrator: $michael, entry: $entry)->execute();

        $this->assertModelMissing($entry);
        $this->assertNull(PreviewListEntry::findByAdmissionToken('roy-anderson-token'));

        Queue::assertPushedOn('low', LogAdministratorAction::class, fn (LogAdministratorAction $job): bool => $job->action === AdministratorActionEnum::PreviewListEntryRemoved
            && $job->administrator->is($michael)
            && $job->parameters === ['email' => 'roy.anderson@dundermifflin.com']);
    }
}
