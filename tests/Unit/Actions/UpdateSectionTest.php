<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateSection;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateSectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renames_a_section(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);

        new UpdateSection(
            user: $user,
            section: $section,
            name: 'Where they live',
            description: 'Street, town, country',
            icon: 'info',
        )->execute();

        $section->refresh();

        $this->assertEquals('Where they live', $section->name);
        $this->assertEquals('Street, town, country', $section->description);
        $this->assertEquals('info', $section->icon);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::SectionUpdate
                && $job->parameters === ['name' => 'Where they live'];
        });
    }

    #[Test]
    public function it_leaves_the_slug_alone_when_the_name_changes(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id, 'slug' => 'address']);

        new UpdateSection(user: $user, section: $section, name: 'Where they live')->execute();

        $this->assertEquals('address', $section->fresh()->slug);
    }

    #[Test]
    public function it_changes_the_mark(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->marked('note', '#3f5fd0')->create(['account_id' => $user->account_id]);

        new UpdateSection(
            user: $user,
            section: $section,
            name: $section->name,
            icon: 'health',
            colour: '#4f8a49',
        )->execute();

        $section->refresh();

        $this->assertEquals('health', $section->icon);
        $this->assertEquals('#4f8a49', $section->colour);
    }

    #[Test]
    public function it_clears_the_mark(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->marked()->create(['account_id' => $user->account_id]);

        new UpdateSection(user: $user, section: $section, name: $section->name)->execute();

        $section->refresh();

        $this->assertNull($section->icon);
        $this->assertNull($section->colour);
    }

    #[Test]
    public function it_refuses_a_section_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $section = Section::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateSection(user: $michael, section: $section, name: 'Address')->execute();
    }
}
