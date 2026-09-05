<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateSection;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\LogUserAction;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateSectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_defines_a_reusable_section(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $section = new CreateSection(
            user: $user,
            name: 'Medical follow up',
            description: 'What the vet asks',
            icon: 'info',
        )->execute();

        $this->assertInstanceOf(Section::class, $section);

        $this->assertDatabaseHas('sections', [
            'id' => $section->id,
            'account_id' => $user->account_id,
            'name' => 'Medical follow up',
            'description' => 'What the vet asks',
            'slug' => 'medical-follow-up',
            'icon' => 'info',
            'is_system' => false,
            'is_active' => true,
            'published_version_id' => null,
        ]);
    }

    #[Test]
    public function it_starts_the_section_with_an_empty_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $section = new CreateSection(user: $user, name: 'Address')->execute();

        $this->assertDatabaseHas('section_versions', [
            'section_id' => $section->id,
            'account_id' => $user->account_id,
            'version_number' => 1,
            'status' => VersionStatusEnum::Draft->value,
            'previous_version_id' => null,
            'usage_count' => 0,
        ]);
    }

    #[Test]
    public function it_takes_the_slug_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $section = new CreateSection(user: $user, name: 'Social networks', slug: 'socials')->execute();

        $this->assertEquals('socials', $section->slug);
    }

    #[Test]
    public function it_counts_up_when_the_slug_it_wants_is_taken(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $first = new CreateSection(user: $user, name: 'Address')->execute();
        $second = new CreateSection(user: $user, name: 'Address')->execute();

        $this->assertEquals('address', $first->slug);
        $this->assertEquals('address-2', $second->slug);
    }

    #[Test]
    public function it_leaves_the_slug_of_another_account_alone(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();

        new CreateSection(user: $michael, name: 'Address')->execute();
        $beets = new CreateSection(user: $dwight, name: 'Address')->execute();

        $this->assertEquals('address', $beets->slug);
    }

    #[Test]
    public function it_writes_the_section_in_the_language_of_whoever_made_it(): void
    {
        Queue::fake();

        $user = User::factory()->create(['locale' => 'fr_FR']);

        $section = new CreateSection(user: $user, name: 'Address')->execute();

        $this->assertEquals('fr_FR', $section->locale);
    }

    #[Test]
    public function it_strips_html_out_of_what_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $section = new CreateSection(user: $user, name: '<script>alert(1)</script>Address')->execute();

        $this->assertEquals('Address', $section->name);
    }

    #[Test]
    public function it_writes_the_creation_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new CreateSection(user: $user, name: 'Address')->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::SectionCreation
                && $job->parameters === ['name' => 'Address'];
        });
    }

    #[Test]
    public function it_stores_the_mark_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $section = new CreateSection(
            user: $user,
            name: 'Health',
            icon: 'health',
            colour: '#4f8a49',
        )->execute();

        $this->assertDatabaseHas('sections', [
            'id' => $section->id,
            'icon' => 'health',
            'colour' => '#4f8a49',
        ]);
    }

    #[Test]
    public function it_leaves_the_mark_empty_when_it_is_given_none(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $section = new CreateSection(user: $user, name: 'Health')->execute();

        $this->assertDatabaseHas('sections', [
            'id' => $section->id,
            'icon' => null,
            'colour' => null,
        ]);
    }
}
