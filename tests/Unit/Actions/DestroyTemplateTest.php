<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateTemplate;
use App\Actions\DestroyTemplate;
use App\Enums\UserActionEnum;
use App\Exceptions\CannotDeleteATemplateInUse;
use App\Jobs\LogUserAction;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_template_nothing_points_at(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        new DestroyTemplate(user: $user, template: $template)->execute();

        $this->assertSoftDeleted($template);
    }

    /**
     * Taking two hundred contacts down with the word "Contact" is not something
     * a single button should be able to do.
     */
    #[Test]
    public function it_refuses_while_records_still_point_at_one_of_its_versions(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        TemplateVersion::factory()->published()->used(214)->create(['template_id' => $template->id]);

        $this->expectException(CannotDeleteATemplateInUse::class);

        new DestroyTemplate(user: $user, template: $template)->execute();
    }

    #[Test]
    public function it_does_not_release_the_slug_of_a_deleted_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = new CreateTemplate(user: $user, name: 'Contact')->execute();

        new DestroyTemplate(user: $user, template: $template)->execute();

        $again = new CreateTemplate(user: $user, name: 'Contact')->execute();

        $this->assertEquals('contact-2', $again->slug);
    }

    #[Test]
    public function it_refuses_a_template_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $template = Template::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroyTemplate(user: $michael, template: $template)->execute();
    }

    #[Test]
    public function it_writes_the_deletion_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Contact']);

        new DestroyTemplate(user: $user, template: $template)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::TemplateDeletion
                && $job->parameters === ['name' => 'Contact'];
        });
    }
}
