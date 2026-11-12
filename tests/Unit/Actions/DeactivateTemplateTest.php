<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DeactivateTemplate;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Template;
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
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Contact']);

        new DeactivateTemplate(user: $user, template: $template)->execute();

        $this->assertFalse($template->fresh()->is_active);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::TemplateDeactivated
                && $job->parameters === ['name' => 'Contact'];
        });
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
}
