<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\LifeEvent;
use App\Models\LifeEventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LifeEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_is_called_by_its_title(): void
    {
        $lifeEvent = LifeEvent::factory()->create(['title' => 'Dinner party at Michael’s condo']);

        $this->assertEquals('Dinner party at Michael’s condo', $lifeEvent->name);
    }

    #[Test]
    public function it_is_called_by_its_type_when_it_has_no_title(): void
    {
        $lifeEvent = LifeEvent::factory()->create(['title' => null]);

        LifeEventType::query()->whereKey($lifeEvent->life_event_type_id)->update(['name' => 'Booze cruise']);

        $this->assertEquals('Booze cruise', $lifeEvent->fresh()?->name);
    }
}
