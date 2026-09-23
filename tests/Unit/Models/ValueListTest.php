<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\ValueListEnum;
use App\Models\ValueList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ValueListTest extends TestCase
{
    use RefreshDatabase;

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
    public function it_is_deleted_softly(): void
    {
        $list = ValueList::factory()->create();

        $list->delete();

        $this->assertSoftDeleted('value_lists', ['id' => $list->id]);
        $this->assertEquals(0, ValueList::query()->count());
    }
}
