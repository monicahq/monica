<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ActivateTemplate;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivateTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_a_template_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->inactive()->create(['account_id' => $user->account_id]);

        new ActivateTemplate(user: $user, template: $template)->execute();

        $this->assertTrue($template->fresh()->is_active);
    }

    #[Test]
    public function it_leaves_an_already_offered_template_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        new ActivateTemplate(user: $user, template: $template)->execute();

        $this->assertTrue($template->fresh()->is_active);
    }

    #[Test]
    public function it_refuses_a_template_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $template = Template::factory()->inactive()->create();

        $this->expectException(ModelNotFoundException::class);

        new ActivateTemplate(user: $michael, template: $template)->execute();
    }

    #[Test]
    public function it_writes_the_change_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->inactive()->create(['account_id' => $user->account_id, 'name' => 'Contact']);

        new ActivateTemplate(user: $user, template: $template)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::TemplateActivated
                && $job->parameters === ['name' => 'Contact'];
        });
    }
}
