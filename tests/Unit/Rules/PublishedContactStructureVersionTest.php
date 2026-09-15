<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Rules\PublishedContactStructureVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublishedContactStructureVersionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_accepts_the_version_an_active_contact_template_publishes(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $this->assertTrue($this->passes($user, $this->publish($template)));
    }

    #[Test]
    public function it_refuses_the_version_of_a_template_that_is_no_longer_offered(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'is_active' => false]);

        $this->assertFalse($this->passes($user, $this->publish($template)));
    }

    #[Test]
    public function it_refuses_a_version_the_template_has_moved_on_from(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $first = $this->publish($template);
        $this->publish($template, 2);

        $this->assertFalse($this->passes($user, $first));
    }

    #[Test]
    public function it_refuses_the_version_of_a_template_describing_an_entity(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->forEntity()->create(['account_id' => $user->account_id]);

        $this->assertFalse($this->passes($user, $this->publish($template)));
    }

    #[Test]
    public function it_refuses_the_version_of_a_deleted_template(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = $this->publish($template);
        $template->delete();

        $this->assertFalse($this->passes($user, $version));
    }

    #[Test]
    public function it_refuses_the_version_of_another_account(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();

        $this->assertFalse($this->passes($user, $this->publish($template)));
    }

    #[Test]
    public function it_refuses_a_version_nobody_has(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->passes($user, 987654));
    }

    private function publish(Template $template, int $number = 1): int
    {
        $version = StructureVersion::factory()->published()->create([
            'structure_id' => $template->structure->id,
            'version_number' => $number,
        ]);
        $template->structure->update(['published_version_id' => $version->id]);

        return $version->id;
    }

    private function passes(User $user, int $versionId): bool
    {
        return Validator::make(
            ['structure_version_id' => $versionId],
            ['structure_version_id' => [new PublishedContactStructureVersion($user->account_id)]],
        )->passes();
    }
}
