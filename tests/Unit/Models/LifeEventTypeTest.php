<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\LifeEventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LifeEventTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_in_the_language_of_whoever_is_looking_until_it_is_renamed(): void
    {
        $supplied = LifeEventType::factory()->supplied()->create();

        $this->assertEquals(__('Call'), $supplied->name);
    }

    #[Test]
    public function it_keeps_the_name_somebody_gave_it(): void
    {
        $lifeEventType = LifeEventType::factory()->create(['name' => 'Sunday roast']);

        $this->assertEquals('Sunday roast', $lifeEventType->name);
    }

    #[Test]
    public function it_can_be_deleted_without_the_row_going(): void
    {
        $lifeEventType = LifeEventType::factory()->create();

        $lifeEventType->delete();

        $this->assertSoftDeleted($lifeEventType);
    }
}
