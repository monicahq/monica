<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\RebuildSearchIndex;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Scout\EngineManager;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RebuildSearchIndexTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_empties_the_index_then_sends_every_record_again(): void
    {
        config(['scout.driver' => 'database']);
        $stale = Contact::factory()->create();
        $missing = Contact::factory()->create();
        Contact::query()->whereKey($stale->id)->toBase()->update(['search_name' => 'Todd Packer, left over']);
        Contact::query()->whereKey($missing->id)->toBase()->update(['search_name' => null]);

        new RebuildSearchIndex(model: Contact::class)->handle(resolve(EngineManager::class));

        $this->assertNotNull($stale->refresh()->search_name);
        $this->assertNotSame('Todd Packer, left over', $stale->search_name);
        $this->assertNotNull($missing->refresh()->search_name);
    }

    #[Test]
    public function it_is_unique_for_one_kind_of_record(): void
    {
        $this->assertSame(Contact::class, new RebuildSearchIndex(model: Contact::class)->uniqueId());
    }
}
