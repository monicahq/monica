<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\ValueListEnum;
use App\Models\Account;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ValueListTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $account = Account::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $account->id]);

        $this->assertTrue($list->account()->exists());
        $this->assertTrue($account->is($list->account));
    }

    #[Test]
    public function it_has_many_items(): void
    {
        $list = ValueList::factory()->create();
        ValueListItem::factory()->create([
            'account_id' => $list->account_id,
            'value_list_id' => $list->id,
            'label' => 'Mobile',
        ]);

        $this->assertTrue($list->items()->exists());
        $this->assertEquals('Mobile', $list->items->first()->label);
    }

    #[Test]
    public function it_reads_by_the_name_the_user_gave_it(): void
    {
        $list = ValueList::factory()->create(['name' => 'Dundie categories']);

        $this->assertEquals('Dundie categories', $list->name);
    }

    #[Test]
    public function it_falls_back_to_its_translation_key_while_it_has_no_name(): void
    {
        $list = ValueList::factory()->supplied(ValueListEnum::PhoneLabels)->create();

        $this->assertNull($list->getRawOriginal('name'));
        $this->assertEquals('Phone labels', $list->name);
    }

    #[Test]
    public function it_reads_a_supplied_name_in_the_language_of_whoever_is_looking(): void
    {
        $list = ValueList::factory()->supplied(ValueListEnum::PhoneLabels)->create();

        $this->app->setLocale('fr_FR');

        $this->assertEquals('Étiquettes de téléphone', $list->name);
    }

    #[Test]
    public function it_casts_what_it_is_and_what_it_allows(): void
    {
        $list = ValueList::factory()->supplied()->create(['position' => 2]);

        $list->refresh();

        $this->assertTrue($list->is_system);
        $this->assertTrue($list->allows_user_values);
        $this->assertSame(2, $list->position);
    }

    #[Test]
    public function it_is_deleted_softly(): void
    {
        $list = ValueList::factory()->create();

        $list->delete();

        $this->assertSoftDeleted('value_lists', ['id' => $list->id]);
        $this->assertEquals(0, ValueList::query()->count());
    }
}
