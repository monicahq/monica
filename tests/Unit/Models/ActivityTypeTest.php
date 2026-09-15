<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\ActivityType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivityTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $activityType = ActivityType::factory()->create();

        $this->assertTrue($activityType->account()->exists());
    }

    #[Test]
    public function it_belongs_to_the_category_it_is_filed_under(): void
    {
        $activityType = ActivityType::factory()->create();

        $this->assertTrue($activityType->activityCategory()->exists());
    }

    #[Test]
    public function it_reads_in_the_language_of_whoever_is_looking_until_it_is_renamed(): void
    {
        $supplied = ActivityType::factory()->supplied()->create();

        $this->assertEquals(__('Call'), $supplied->name);
    }

    #[Test]
    public function it_keeps_the_name_somebody_gave_it(): void
    {
        $activityType = ActivityType::factory()->create(['name' => 'Sunday roast']);

        $this->assertEquals('Sunday roast', $activityType->name);
    }

    #[Test]
    public function it_can_be_deleted_without_the_row_going(): void
    {
        $activityType = ActivityType::factory()->create();

        $activityType->delete();

        $this->assertSoftDeleted($activityType);
    }
}
