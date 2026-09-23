<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\LifeEventCategoryEnum;
use App\Models\Account;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LifeEventCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_account_has_many_of_them_in_order(): void
    {
        $account = Account::factory()->create();
        LifeEventCategory::factory()->create(['account_id' => $account->id, 'name' => 'Second', 'position' => 2]);
        LifeEventCategory::factory()->create(['account_id' => $account->id, 'name' => 'First', 'position' => 1]);

        $this->assertCount(2, $account->lifeEventCategories);
        $this->assertEquals('First', $account->lifeEventCategories->first()->name);
    }

    #[Test]
    public function it_reads_the_supplied_wording_through_the_translator(): void
    {
        $category = LifeEventCategory::factory()->system(LifeEventCategoryEnum::TravelAndExperiences)->create();

        $this->assertEquals(__('Travel and experiences'), $category->name);
        $this->assertNull($category->getRawOriginal('name'));
    }

    #[Test]
    public function it_reads_the_words_the_user_wrote_when_there_are_any(): void
    {
        $category = LifeEventCategory::factory()->system(LifeEventCategoryEnum::Personal)->create([
            'name' => 'Pour moi',
        ]);

        $this->assertEquals('Pour moi', $category->name);
    }

    #[Test]
    public function it_counts_the_life_event_natures_filed_under_it(): void
    {
        $category = LifeEventCategory::factory()->create();
        $elsewhere = LifeEventCategory::factory()->create();

        LifeEventType::factory()->count(3)->create(['life_event_category_id' => $category->id]);
        LifeEventType::factory()->create(['life_event_category_id' => $elsewhere->id]);

        $this->assertSame(3, $category->lifeEventTypeCount());
        $this->assertSame(1, $elsewhere->lifeEventTypeCount());
    }

    #[Test]
    public function it_does_not_count_a_deleted_life_event_nature(): void
    {
        $category = LifeEventCategory::factory()->create();
        $type = LifeEventType::factory()->create(['life_event_category_id' => $category->id]);

        $type->delete();

        $this->assertSame(0, $category->lifeEventTypeCount());
    }

    #[Test]
    public function it_is_soft_deleted(): void
    {
        $category = LifeEventCategory::factory()->create();

        $category->delete();

        $this->assertSoftDeleted($category);
        $this->assertNull(LifeEventCategory::query()->find($category->id));
    }
}
