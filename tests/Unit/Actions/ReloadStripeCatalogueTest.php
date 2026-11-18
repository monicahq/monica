<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ReloadStripeCatalogue;
use App\Enums\AdministratorActionEnum;
use App\Helpers\StripeCatalogue;
use App\Jobs\LogAdministratorAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReloadStripeCatalogueTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_the_catalogue_again_and_logs_who_did_it(): void
    {
        Queue::fake();
        $client = $this->fakeStripe();
        $michael = User::factory()->create();
        StripeCatalogue::offers();

        $isAvailable = new ReloadStripeCatalogue(administrator: $michael)->execute();

        $this->assertTrue($isAvailable);
        $this->assertCount(2, $client->requests('GET prices'));
        Queue::assertPushedOn('low', LogAdministratorAction::class, fn (LogAdministratorAction $job): bool => $job->action === AdministratorActionEnum::StripeCatalogueReloaded
            && $job->administrator?->is($michael) === true);
    }

    #[Test]
    public function it_reports_that_the_catalogue_could_not_be_read(): void
    {
        Queue::fake();
        $client = $this->fakeStripe();
        $client->failOn('prices*');

        $this->assertFalse(new ReloadStripeCatalogue(administrator: User::factory()->create())->execute());
    }
}
