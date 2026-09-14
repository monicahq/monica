<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\ActivityCategoryEnum;
use App\Models\Account;
use App\Models\ActivityCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivityCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $account = Account::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $account->id]);

        $this->assertTrue($category->account()->exists());
        $this->assertTrue($account->is($category->account));
    }

    #[Test]
    public function an_account_has_many_of_them_in_order(): void
    {
        $account = Account::factory()->create();
        ActivityCategory::factory()->create(['account_id' => $account->id, 'name' => 'Second', 'position' => 2]);
        ActivityCategory::factory()->create(['account_id' => $account->id, 'name' => 'First', 'position' => 1]);

        $this->assertCount(2, $account->activityCategories);
        $this->assertEquals('First', $account->activityCategories->first()->name);
    }

    #[Test]
    public function it_reads_the_supplied_wording_through_the_translator(): void
    {
        $category = ActivityCategory::factory()->system(ActivityCategoryEnum::TravelAndExperiences)->create();

        $this->assertEquals(__('Travel and experiences'), $category->name);
        $this->assertNull($category->getRawOriginal('name'));
    }

    #[Test]
    public function it_reads_the_words_the_user_wrote_when_there_are_any(): void
    {
        $category = ActivityCategory::factory()->system(ActivityCategoryEnum::Personal)->create([
            'name' => 'Pour moi',
        ]);

        $this->assertEquals('Pour moi', $category->name);
    }

    #[Test]
    public function it_casts_what_it_holds(): void
    {
        $category = ActivityCategory::factory()->system()->create(['position' => 3]);

        $this->assertIsBool($category->is_system);
        $this->assertIsBool($category->is_active);
        $this->assertIsInt($category->position);
        $this->assertTrue($category->is_system);
        $this->assertTrue($category->is_active);
        $this->assertSame(3, $category->position);
    }

    #[Test]
    public function it_holds_no_activity_nature_until_that_record_exists(): void
    {
        $category = ActivityCategory::factory()->create();

        $this->assertSame(0, $category->activityTypeCount());
    }

    #[Test]
    public function it_is_soft_deleted(): void
    {
        $category = ActivityCategory::factory()->create();

        $category->delete();

        $this->assertSoftDeleted($category);
        $this->assertNull(ActivityCategory::query()->find($category->id));
    }
}
