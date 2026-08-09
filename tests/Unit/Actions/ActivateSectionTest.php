<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ActivateSection;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivateSectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_a_section_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->inactive()->create(['account_id' => $user->account_id]);

        new ActivateSection(user: $user, section: $section)->execute();

        $this->assertTrue($section->fresh()->is_active);
    }

    #[Test]
    public function it_refuses_a_section_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $section = Section::factory()->inactive()->create();

        $this->expectException(ModelNotFoundException::class);

        new ActivateSection(user: $michael, section: $section)->execute();
    }

    #[Test]
    public function it_writes_the_change_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->inactive()->create(['account_id' => $user->account_id, 'name' => 'Address']);

        new ActivateSection(user: $user, section: $section)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::SectionActivated
                && $job->parameters === ['name' => 'Address'];
        });
    }
}
