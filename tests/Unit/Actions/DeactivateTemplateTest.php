<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DeactivateTemplate;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeactivateTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stops_a_template_being_offered(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        new DeactivateTemplate(user: $user, template: $template)->execute();

        $this->assertFalse($template->fresh()->is_active);
    }

    /**
     * Deactivating is the answer to a template somebody has finished with, and
     * it is a different thing from deleting: the records stay exactly as they
     * are, on the version they were made with.
     */
    #[Test]
    public function it_leaves_the_records_already_on_it_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->published()->used(31)->create(['template_id' => $template->id]);

        new DeactivateTemplate(user: $user, template: $template)->execute();

        $this->assertEquals(31, $version->fresh()->record_count);
    }

    #[Test]
    public function it_refuses_a_template_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $template = Template::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DeactivateTemplate(user: $michael, template: $template)->execute();
    }

    #[Test]
    public function it_writes_the_change_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Contact']);

        new DeactivateTemplate(user: $user, template: $template)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::TemplateDeactivated
                && $job->parameters === ['name' => 'Contact'];
        });
    }
}
