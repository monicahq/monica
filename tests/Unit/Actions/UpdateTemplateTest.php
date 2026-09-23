<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateTemplate;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renames_a_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Pet']);

        new UpdateTemplate(
            user: $user,
            template: $template,
            name: 'Companion animal',
            description: 'Everything the vet asks',
        )->execute();

        $template->refresh();

        $this->assertEquals('Companion animal', $template->name);
        $this->assertEquals('Everything the vet asks', $template->description);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::TemplateUpdate
                && $job->parameters === ['name' => 'Companion animal'];
        });
    }

    #[Test]
    public function it_leaves_the_slug_alone_when_the_name_changes(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'slug' => 'pet']);

        new UpdateTemplate(user: $user, template: $template, name: 'Companion animal')->execute();

        $this->assertEquals('pet', $template->fresh()->slug);
    }

    #[Test]
    public function it_changes_the_language_the_template_is_written_in(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'locale' => 'en']);

        new UpdateTemplate(user: $user, template: $template, name: 'Contact', locale: 'fr_FR')->execute();

        $this->assertEquals('fr_FR', $template->fresh()->locale);
    }

    #[Test]
    public function it_refuses_a_template_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $template = Template::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateTemplate(user: $michael, template: $template, name: 'Contact')->execute();
    }
}
